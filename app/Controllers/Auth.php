<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    protected $helpers = ['url', 'form'];

    public function login()
    {
        session();
        $data = [
            'title' => 'Login',
            'validation' => \Config\Services::validation()
        ];
        return view('auth/login', $data);
    }

    public function auth()
    {
        $authStart = hrtime(true);

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

        if (!$validation) {
            return redirect()->to('login')->withInput();
        }

        $validationMs = round((hrtime(true) - $authStart) / 1_000_000, 3);
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $db = \Config\Database::connect();
        $dbStart = hrtime(true);

        // Single login lookup. For students, pleton name is resolved here as well,
        // avoiding the second pleton query after password verification.
        $data = $db->table('users')
            ->select(''
                . 'users.id, users.username, users.password, users.role_id, '
                . 'roles.nama_role, '
                . 'pegawai.id as pegawai_id, pegawai.nama as nama_pegawai, '
                . 'pegawai.nomor_induk as pegawai_nomor_induk, '
                . 'siswa.nama as nama_siswa, siswa.pleton_id as siswa_pleton_id, '
                . 'pleton_siswa.nama_pleton as siswa_nama_pleton'
            )
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->join('pegawai', 'pegawai.user_id = users.id', 'left')
            ->join('siswa', 'siswa.user_id = users.id', 'left')
            ->join('pleton as pleton_siswa', 'pleton_siswa.id = siswa.pleton_id', 'left')
            ->where('users.username', $username)
            ->get()
            ->getRowArray();

        $dbMs = round((hrtime(true) - $dbStart) / 1_000_000, 3);

        $passwordStart = hrtime(true);
        $passwordValid = $data && password_verify($password, $data['password']);
        $passwordMs = round((hrtime(true) - $passwordStart) / 1_000_000, 3);

        if (!$data || !$passwordValid) {
            $authMs = round((hrtime(true) - $authStart) / 1_000_000, 3);
            log_message('info', 'AUTH_TIMING result=FAIL username={username} total={total}ms validation={validation}ms db={db}ms password={password}ms', [
                'username' => $username,
                'total' => $authMs,
                'validation' => $validationMs,
                'db' => $dbMs,
                'password' => $passwordMs,
            ]);

            session()->setFlashdata('msg', 'Username atau Password salah');
            return redirect()->to('/login');
        }

        $roleId = (int) $data['role_id'];
        $namaRole = $data['nama_role'] ?? 'User';
        $nama = ($roleId === 7)
            ? ($data['nama_siswa'] ?? 'User')
            : ($data['nama_pegawai'] ?? 'User');

        $nama_pleton = '';
        $nama_kompi = '';
        $nama_batalyon = '';
        $unitMs = 0.0;

        // Unit lookup remains only for staff roles that require it.
        // Student pleton is already resolved by the single login query above.
        $unitTableByRole = [
            4 => ['table' => 'pleton',   'col' => 'danton_id', 'nameCol' => 'nama_pleton',   'var' => 'nama_pleton'],
            5 => ['table' => 'kompi',    'col' => 'danki_id',  'nameCol' => 'nama_kompi',    'var' => 'nama_kompi'],
            6 => ['table' => 'batalyon', 'col' => 'danyon_id', 'nameCol' => 'nama_batalyon', 'var' => 'nama_batalyon'],
        ];

        $unitStart = hrtime(true);

        if (isset($unitTableByRole[$roleId]) && $data['pegawai_id']) {
            $u = $unitTableByRole[$roleId];
            $row = $db->table($u['table'])
                ->where($u['col'], $data['pegawai_nomor_induk'])
                ->orWhere($u['col'], $data['pegawai_id'])
                ->get()
                ->getRow();

            if ($row) {
                $value = $row->{$u['nameCol']};
                if ($u['var'] === 'nama_pleton') $nama_pleton = ' - ' . $value;
                if ($u['var'] === 'nama_kompi') $nama_kompi = ' - ' . $value;
                if ($u['var'] === 'nama_batalyon') $nama_batalyon = ' - ' . $value;
            }
        } elseif ($roleId === 7 && !empty($data['siswa_nama_pleton'])) {
            $nama_pleton = ' - ' . $data['siswa_nama_pleton'];
        }

        $unitMs = round((hrtime(true) - $unitStart) / 1_000_000, 3);

        // One session write after all values have been resolved.
        $sessionStart = hrtime(true);

        $sessionData = [
            'user_id'       => $data['id'],
            'username'      => $data['username'],
            'role_id'       => $roleId,
            'nama_role'     => $namaRole,
            'nama_pleton'   => $nama_pleton,
            'nama_kompi'    => $nama_kompi,
            'nama_batalyon' => $nama_batalyon,
            'nama'          => $nama,
            'logged_in'     => true
        ];

        session()->set($sessionData);

        $sessionMs = round((hrtime(true) - $sessionStart) / 1_000_000, 3);
        $authMs = round((hrtime(true) - $authStart) / 1_000_000, 3);

        log_message('info', 'AUTH_TIMING result=SUCCESS username={username} total={total}ms validation={validation}ms db={db}ms password={password}ms unit={unit}ms session={session}ms', [
            'username' => $username,
            'total' => $authMs,
            'validation' => $validationMs,
            'db' => $dbMs,
            'password' => $passwordMs,
            'unit' => $unitMs,
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
