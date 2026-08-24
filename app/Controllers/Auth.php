<?php

namespace App\Controllers;

class Auth extends BaseController
{
    protected $helpers = ['url', 'form'];

    private const PASSWORD_BCRYPT_COST = 8;

    private function timingEnabled(): bool
    {
        return ENVIRONMENT === 'development'
            && filter_var(env('AUTH_TIMING_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function logTiming(string $message, array $context = []): void
    {
        if (!$this->timingEnabled()) {
            return;
        }

        log_message('info', $message, $context);
    }

    private function writeAuthTiming(array $row): void
    {
        if (!$this->timingEnabled()) {
            return;
        }

        $directory = WRITEPATH . 'logs';
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $path = $directory . DIRECTORY_SEPARATOR . 'auth-timing.csv';
        $handle = @fopen($path, 'ab');
        if ($handle === false) {
            return;
        }

        try {
            if (flock($handle, LOCK_EX)) {
                if (filesize($path) === 0) {
                    fputcsv($handle, [
                        'timestamp', 'username', 'result', 'validation_ms',
                        'request_ms', 'db_ms', 'password_ms', 'unit_ms',
                        'unit_queries', 'session_ms', 'rehash_ms', 'total_ms'
                    ]);
                }
                fputcsv($handle, $row);
                fflush($handle);
                flock($handle, LOCK_UN);
            }
        } finally {
            fclose($handle);
        }
    }

    public function login()
    {
        $timingStart = hrtime(true);

        $sessionStart = hrtime(true);
        session();
        $sessionMs = round((hrtime(true) - $sessionStart) / 1_000_000, 3);

        $validationStart = hrtime(true);
        $validation = \Config\Services::validation();
        $validationMs = round((hrtime(true) - $validationStart) / 1_000_000, 3);

        $viewStart = hrtime(true);
        $response = $this->response->setBody(view('auth/login', [
            'title' => 'Login',
            'validation' => $validation,
        ]));
        $viewMs = round((hrtime(true) - $viewStart) / 1_000_000, 3);

        $totalMs = round((hrtime(true) - $timingStart) / 1_000_000, 3);

        $this->logTiming(
            'LOGIN_PAGE_TIMING total={total}ms session={session}ms validation={validation}ms view={view}ms',
            [
                'total' => $totalMs,
                'session' => $sessionMs,
                'validation' => $validationMs,
                'view' => $viewMs,
            ]
        );

        return $response;
    }

    public function auth()
    {
        $authStart = hrtime(true);
        $timestamp = date('c');

        $validationStart = hrtime(true);
        $validation = $this->validate([
            'username' => [
                'label' => 'username',
                'rules' => 'required',
                'errors' => ['required' => 'username harus di isi']
            ],
            'password' => [
                'label' => 'password',
                'rules' => 'required|min_length[3]',
                'errors' => ['required' => 'password harus di isi']
            ]
        ]);
        $validationMs = round((hrtime(true) - $validationStart) / 1_000_000, 3);

        if (!$validation) {
            $totalMs = round((hrtime(true) - $authStart) / 1_000_000, 3);
            $this->writeAuthTiming([
                $timestamp, '', 'VALIDATION_FAIL', $validationMs,
                0, 0, 0, 0, 0, 0, 0, $totalMs
            ]);
            $this->logTiming('AUTH_TIMING result=VALIDATION_FAIL total={total}ms validation={validation}ms', [
                'total' => $totalMs,
                'validation' => $validationMs,
            ]);
            return redirect()->to('login')->withInput();
        }

        $requestStart = hrtime(true);
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $requestMs = round((hrtime(true) - $requestStart) / 1_000_000, 3);

        $db = \Config\Database::connect();

        $dbStart = hrtime(true);
        $user = $db->table('users')
            ->select('id, username, password, role_id')
            ->where('username', $username)
            ->limit(1)
            ->get()
            ->getRowArray();
        $dbMs = round((hrtime(true) - $dbStart) / 1_000_000, 3);

        $passwordStart = hrtime(true);
        $passwordValid = $user && password_verify($password, $user['password']);
        $passwordMs = round((hrtime(true) - $passwordStart) / 1_000_000, 3);

        if (!$user || !$passwordValid) {
            $flashStart = hrtime(true);
            session()->setFlashdata('msg', 'Username atau Password salah');
            $flashMs = round((hrtime(true) - $flashStart) / 1_000_000, 3);

            $authMs = round((hrtime(true) - $authStart) / 1_000_000, 3);
            $this->writeAuthTiming([
                $timestamp, $username, 'FAIL', $validationMs,
                $requestMs, $dbMs, $passwordMs, 0, 0, $flashMs, 0, $authMs
            ]);
            $this->logTiming('AUTH_TIMING result=FAIL username={username} total={total}ms validation={validation}ms request={request}ms db={db}ms password={password}ms flash={flash}ms', [
                'username' => $username,
                'total' => $authMs,
                'validation' => $validationMs,
                'request' => $requestMs,
                'db' => $dbMs,
                'password' => $passwordMs,
                'flash' => $flashMs,
            ]);

            return redirect()->to('/login');
        }

        $rehashMs = 0.0;
        if (password_needs_rehash(
            $user['password'],
            PASSWORD_BCRYPT,
            ['cost' => self::PASSWORD_BCRYPT_COST]
        )) {
            $rehashStart = hrtime(true);
            $newHash = password_hash(
                $password,
                PASSWORD_BCRYPT,
                ['cost' => self::PASSWORD_BCRYPT_COST]
            );

            if ($newHash !== false) {
                $db->table('users')
                    ->where('id', (int) $user['id'])
                    ->update(['password' => $newHash]);
            }

            $rehashMs = round((hrtime(true) - $rehashStart) / 1_000_000, 3);
        }

        $roleId = (int) $user['role_id'];
        $namaRole = 'User';
        $nama = 'User';
        $nama_pleton = '';
        $nama_kompi = '';
        $nama_batalyon = '';
        $unitQueryCount = 0;
        $unitStart = hrtime(true);

        if ($roleId === 7) {
            $profile = $db->table('siswa')
                ->select('siswa.nama, siswa.pleton_id, pleton.nama_pleton')
                ->join('pleton', 'pleton.id = siswa.pleton_id', 'left')
                ->where('siswa.user_id', (int) $user['id'])
                ->limit(1)
                ->get()
                ->getRowArray();
            $unitQueryCount++;

            $nama = $profile['nama'] ?? 'User';
            if (!empty($profile['nama_pleton'])) {
                $nama_pleton = ' - ' . $profile['nama_pleton'];
            }
        } else {
            $pegawai = $db->table('pegawai')
                ->select('id, nama, nomor_induk')
                ->where('user_id', (int) $user['id'])
                ->limit(1)
                ->get()
                ->getRowArray();
            $unitQueryCount++;

            $nama = $pegawai['nama'] ?? 'User';

            $unitTableByRole = [
                4 => ['table' => 'pleton',   'col' => 'danton_id', 'nameCol' => 'nama_pleton',   'var' => 'nama_pleton'],
                5 => ['table' => 'kompi',    'col' => 'danki_id',  'nameCol' => 'nama_kompi',    'var' => 'nama_kompi'],
                6 => ['table' => 'batalyon', 'col' => 'danyon_id', 'nameCol' => 'nama_batalyon', 'var' => 'nama_batalyon'],
            ];

            if (isset($unitTableByRole[$roleId]) && $pegawai) {
                $u = $unitTableByRole[$roleId];

                $row = $db->table($u['table'])
                    ->select($u['nameCol'])
                    ->where($u['col'], (int) $pegawai['id'])
                    ->limit(1)
                    ->get()
                    ->getRow();
                $unitQueryCount++;

                if (!$row && !empty($pegawai['nomor_induk'])) {
                    $row = $db->table($u['table'])
                        ->select($u['nameCol'])
                        ->where($u['col'], $pegawai['nomor_induk'])
                        ->limit(1)
                        ->get()
                        ->getRow();
                    $unitQueryCount++;
                }

                if ($row) {
                    $value = $row->{$u['nameCol']};
                    if ($u['var'] === 'nama_pleton') $nama_pleton = ' - ' . $value;
                    if ($u['var'] === 'nama_kompi') $nama_kompi = ' - ' . $value;
                    if ($u['var'] === 'nama_batalyon') $nama_batalyon = ' - ' . $value;
                }
            }
        }

        $unitMs = round((hrtime(true) - $unitStart) / 1_000_000, 3);

        $role = $db->table('roles')
            ->select('nama_role')
            ->where('id', $roleId)
            ->limit(1)
            ->get()
            ->getRowArray();
        $unitQueryCount++;
        $namaRole = $role['nama_role'] ?? 'User';

        $sessionStart = hrtime(true);
        session()->set([
            'user_id'       => $user['id'],
            'username'      => $user['username'],
            'role_id'       => $roleId,
            'nama_role'     => $namaRole,
            'nama_pleton'   => $nama_pleton,
            'nama_kompi'    => $nama_kompi,
            'nama_batalyon' => $nama_batalyon,
            'nama'          => $nama,
            'logged_in'     => true
        ]);
        $sessionMs = round((hrtime(true) - $sessionStart) / 1_000_000, 3);

        $authMs = round((hrtime(true) - $authStart) / 1_000_000, 3);
        $this->writeAuthTiming([
            $timestamp, $username, 'SUCCESS', $validationMs,
            $requestMs, $dbMs, $passwordMs, $unitMs,
            $unitQueryCount, $sessionMs, $rehashMs, $authMs
        ]);
        $this->logTiming('AUTH_TIMING result=SUCCESS username={username} total={total}ms validation={validation}ms request={request}ms db={db}ms password={password}ms unit={unit}ms unit_queries={unit_queries} session={session}ms rehash={rehash}ms', [
            'username' => $username,
            'total' => $authMs,
            'validation' => $validationMs,
            'request' => $requestMs,
            'db' => $dbMs,
            'password' => $passwordMs,
            'unit' => $unitMs,
            'unit_queries' => $unitQueryCount,
            'session' => $sessionMs,
            'rehash' => $rehashMs,
        ]);

        if ($roleId === 7) {
            return redirect()->to('/siswa/users/profil');
        }
        return redirect()->to('/dashboard');
    }

    public function benchmarkPasswordVerify()
    {
        if (ENVIRONMENT !== 'development') {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Not available']);
        }

        $password = $this->request->getPost('password');
        $hash = $this->request->getPost('hash');

        if (!$password || !$hash) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'password and hash are required']);
        }

        $start = hrtime(true);
        $valid = password_verify($password, $hash);
        $durationMs = (hrtime(true) - $start) / 1_000_000;

        return $this->response->setJSON([
            'valid' => $valid,
            'duration_ms' => round($durationMs, 3),
        ]);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}
