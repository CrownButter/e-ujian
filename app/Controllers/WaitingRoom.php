<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class WaitingRoom extends BaseController
{
    private const DEFAULT_CAPACITY = 10;
    private const TICKET_TTL_SECONDS = 120;
    private const READY_TTL_SECONDS = 30;

    private function capacity(): int
    {
        $value = (int) env('LOGIN_WAITING_ROOM_CAPACITY', self::DEFAULT_CAPACITY);
        return max(1, min($value, 50));
    }

    private function statePath(): string
    {
        $directory = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'waiting-room';

        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        return $directory . DIRECTORY_SEPARATOR . 'login.json';
    }

    private function withState(callable $callback)
    {
        $path = $this->statePath();
        $handle = @fopen($path, 'c+');

        if ($handle === false) {
            return null;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return null;
            }

            rewind($handle);
            $contents = stream_get_contents($handle);
            $state = json_decode($contents ?: '{}', true);

            if (!is_array($state)) {
                $state = [];
            }

            if (!isset($state['tickets']) || !is_array($state['tickets'])) {
                $state['tickets'] = [];
            }

            $now = time();
            foreach ($state['tickets'] as $token => $ticket) {
                if (!is_array($ticket)) {
                    unset($state['tickets'][$token]);
                    continue;
                }

                $expiresAt = (int) ($ticket['expires_at'] ?? 0);
                if ($expiresAt <= $now) {
                    unset($state['tickets'][$token]);
                }
            }

            $result = $callback($state, $now);

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($state, JSON_UNESCAPED_SLASHES));
            fflush($handle);
            flock($handle, LOCK_UN);

            return $result;
        } finally {
            fclose($handle);
        }
    }

    private function json(array $data, int $status = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setJSON($data);
    }

    public function enter(): ResponseInterface
    {
        $result = $this->withState(function (array &$state, int $now): array {
            $token = bin2hex(random_bytes(32));

            $state['tickets'][$token] = [
                'created_at' => $now,
                'status' => 'waiting',
                'expires_at' => $now + self::TICKET_TTL_SECONDS,
            ];

            $this->promote($state, $now);

            $ticket = $state['tickets'][$token];
            $position = $this->position($state, $token);

            return [
                'ticket' => $token,
                'status' => $ticket['status'],
                'position' => $position,
                'capacity' => $this->capacity(),
                'retry_after' => 2,
            ];
        });

        if ($result === null) {
            return $this->json(['ok' => false, 'message' => 'Waiting room sedang sibuk. Silakan coba lagi.'], 503);
        }

        $result['csrf_token'] = csrf_hash();
        return $this->json(['ok' => true] + $result);
    }

    public function status(): ResponseInterface
    {
        $token = trim((string) $this->request->getGet('ticket'));

        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return $this->json(['ok' => false, 'message' => 'Ticket tidak valid.'], 400);
        }

        $result = $this->withState(function (array &$state, int $now) use ($token): array {
            $this->promote($state, $now);

            if (!isset($state['tickets'][$token])) {
                return [
                    'ok' => false,
                    'message' => 'Ticket sudah kedaluwarsa. Silakan masuk kembali ke waiting room.',
                    'expired' => true,
                ];
            }

            $ticket = $state['tickets'][$token];
            $position = $this->position($state, $token);

            if ($ticket['status'] === 'ready') {
                return [
                    'ok' => true,
                    'status' => 'ready',
                    'position' => 0,
                    'capacity' => $this->capacity(),
                    'retry_after' => 1,
                ];
            }

            return [
                'ok' => true,
                'status' => 'waiting',
                'position' => $position,
                'capacity' => $this->capacity(),
                'retry_after' => 2,
            ];
        });

        if ($result === null) {
            return $this->json(['ok' => false, 'message' => 'Waiting room sedang sibuk.'], 503);
        }

        $status = (($result['expired'] ?? false) === true) ? 410 : 200;
        return $this->json($result, $status);
    }

    public function consumeTicket(string $token): bool
    {
        $token = trim($token);

        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }

        $result = $this->withState(function (array &$state, int $now) use ($token): bool {
            $this->promote($state, $now);

            if (!isset($state['tickets'][$token])) {
                return false;
            }

            $ticket = $state['tickets'][$token];
            if (($ticket['status'] ?? '') !== 'ready') {
                return false;
            }

            unset($state['tickets'][$token]);
            return true;
        });

        return $result === true;
    }

    private function promote(array &$state, int $now): void
    {
        $readyCount = 0;

        foreach ($state['tickets'] as $ticket) {
            if (($ticket['status'] ?? '') === 'ready') {
                $readyCount++;
            }
        }

        $available = max(0, $this->capacity() - $readyCount);
        if ($available === 0) {
            return;
        }

        $waiting = [];
        foreach ($state['tickets'] as $token => $ticket) {
            if (($ticket['status'] ?? '') === 'waiting') {
                $waiting[] = [
                    'token' => $token,
                    'created_at' => (int) ($ticket['created_at'] ?? $now),
                ];
            }
        }

        usort($waiting, static function (array $a, array $b): int {
            return $a['created_at'] <=> $b['created_at'];
        });

        foreach (array_slice($waiting, 0, $available) as $item) {
            $token = $item['token'];
            $state['tickets'][$token]['status'] = 'ready';
            $state['tickets'][$token]['ready_at'] = $now;
            $state['tickets'][$token]['expires_at'] = $now + self::READY_TTL_SECONDS;
        }
    }

    private function position(array $state, string $token): int
    {
        if (!isset($state['tickets'][$token])) {
            return 0;
        }

        if (($state['tickets'][$token]['status'] ?? '') === 'ready') {
            return 0;
        }

        $waiting = [];
        foreach ($state['tickets'] as $currentToken => $ticket) {
            if (($ticket['status'] ?? '') === 'waiting') {
                $waiting[] = [
                    'token' => $currentToken,
                    'created_at' => (int) ($ticket['created_at'] ?? 0),
                ];
            }
        }

        usort($waiting, static function (array $a, array $b): int {
            return $a['created_at'] <=> $b['created_at'];
        });

        foreach ($waiting as $index => $item) {
            if ($item['token'] === $token) {
                return $index + 1;
            }
        }

        return 0;
    }
}
