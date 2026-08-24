<?php

namespace App\Controllers;

class Auth extends BaseController
{
    protected $helpers = ['url', 'form'];

    private function timingEnabled(): bool
    {
        return ENVIRONMENT === 'development'
            && filter_var(env('AUTH_TIMING_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function logTiming(string $message, array $context = []): void
    {
        if ($this->timingEnabled()) {
            log_message('info', $message, $context);
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
        $timingEnabled = $this->timingEnabled();

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
        $profile = $db->table('users')
            ->select('users.id, users.username, users.password, users.role_id, '
                . 'roles.nama_role, '
                . 'pegawai.id as pegawai_id, pegawai.nama as nama_pegawai, '
                . 'pegawai.nomor_induk as pegawai_nomor_induk, '
                . 'siswa.nama as nama_siswa, siswa.pleton_id as siswa_pleton_id, '
                . 'pleton_siswa.nama_pleton as siswa_nama_pleton')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->join('pegawai', 'pegawai.user_id = users.id', 'left')
            ->join('siswa', 'siswa.user_id = users.id', 'left')
            ->join('pleton as pleton_siswa', 'pleton_siswa.id = siswa.pleton_id', 'left')
            ->where('users.username', $username)
            ->get()
            ->getRowArray();
        $dbMs = round((hrtime(true) - $dbStart) / 1_000_000, 3);

        $passwordStart = hrtime(true);
        $passwordValid = $profile && password_verify($password, $profile['password']);
        $passwordMs = round((hrtime(true) - $passwordStart) / 1_000_000, 3);

        if (!$profile || !$passwordValid) {
            $flashStart = hrtime(true);
            session()->setFlashdata('msg', 'Username atau Password salah');
            $flashMs = round((hrtime(true) - $flashStart) / 1_000_000, 3);

            $authMs = round((hrtime(true) - $authStart) / 1_000_000, 3);
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

        $roleId = (int) $profile['role_id'];
        $namaRole = $profile['nama_role'] ?? 'User';
        $nama = ($roleId === 7)
            ? ($profile['nama_siswa'] ?? 'User')
            : ($profile['nama_pegawai'] ?? 'User');

        $nama_pleton = '';
        $nama_kompi = '';
        $nama_batalyon = '';

        $unitTableByRole = [
            4 => ['table' => 'pleton',   'col' => 'danton_id', 'nameCol' => 'nama_pleton',   'var' => 'nama_pleton'],
            5 => ['table' => 'kompi',    'col' => 'danki_id',  'nameCol' => 'nama_kompi',    'var' => 'nama_kompi'],
            6 => ['table' => 'batalyon', 'col' => 'danyon_id', 'nameCol' => 'nama_batalyon', 'var' => 'nama_batalyon'],
        ];

        $unitStart = hrtime(true);
        $unitQueryCount = 0;

        if (isset($unitTableByRole[$roleId]) && !empty($profile['pegawai_id'])) {
            $u = $unitTableByRole[$roleId];

            $unitQueryCount++;
            $row = $db->table($u['table'])
                ->select($u['nameCol'])
                ->where($u['col'], (int) $profile['pegawai_id'])
                ->get()
                ->getRow();

            if (!$row && !empty($profile['pegawai_nomor_induk'])) {
                $unitQueryCount++;
                $row = $db->table($u['table'])
                    ->select($u['nameCol'])
                    ->where($u['col'], $profile['pegawai_nomor_induk'])
                    ->get()
                    ->getRow();
            }

            if ($row) {
                $value = $row->{$u['nameCol']};
                if ($u['var'] === 'nama_pleton') $nama_pleton = ' - ' . $value;
                if ($u['var'] === 'nama_kompi') $nama_kompi = ' - ' . $value;
                if ($u['var'] === 'nama_batalyon') $nama_batalyon = ' - ' . $value;
            }
        } elseif ($roleId === 7 && !empty($profile['siswa_nama_pleton'])) {
            $nama_pleton = ' - ' . $profile['siswa_nama_pleton'];
        }

        $unitMs = round((hrtime(true) - $unitStart) / 1_000_000, 3);

        $sessionStart = hrtime(true);
        session()->set([
            'user_id'       => $profile['id'],
            'username'      => $profile['username'],
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
        $this->logTiming('AUTH_TIMING result=SUCCESS username={username} total={total}ms validation={validation}ms request={request}ms db={db}ms password={password}ms unit={unit}ms unit_queries={unit_queries} session={session}ms', [
            'username' => $username,
            'total' => $authMs,
            'validation' => $validationMs,
            'request' => $requestMs,
            'db' => $dbMs,
            'password' => $passwordMs,
            'unit' => $unitMs,
            'unit_queries' => $unitQueryCount,
            'session' => $sessionMs,
        ]);

        if ($roleId === 7) {
            return redirect()->to('/siswa/users/profil');
        }
        return redirect()->to('/dashboard');
    }

    /**
     * Development-only benchmark endpoint for isolating password_verify().
     * Never expose this endpoint in production.
     */
    public function benchmarkPasswordVerify()
    {
        if (ENVIRONMENT !== 'development') {
            return $this->response->setStatusCode(404)->setJSON([
                'error' => 'Not available'
            ]);
        }

        $password = $this->request->getPost('password');
        $hash = $this->request->getPost('hash');

        if (!$password || !$hash) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => 'password and hash are required'
            ]);
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
