<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    protected $helpers = ['url', 'form'];

    private const ROLE_MAP = [
        1 => 'admin',
        2 => 'operator',
        3 => 'gadik',
        4 => 'danton',
        5 => 'danki',
        6 => 'danyon',
        7 => 'siswa',
    ];

    public function login()
    {
        session();

        $data = [
            'title'      => 'Login',
            'validation' => \Config\Services::validation(),
        ];

        return view('auth/login', $data);
    }

    public function auth()
    {
        $profileEnabled = $this->request->getHeaderLine('X-Auth-Profile') === '1';
        $profileStart = microtime(true);
        $profile = [];

        $validationStart = microtime(true);

        $validation = $this->validate([
            'username' => [
                'label' => 'username',
                'rules' => 'required',
                'errors' => [
                    'required' => 'username harus di isi',
                ],
            ],
            'password' => [
                'label' => 'password',
                'rules' => 'required|min_length[3]',
                'errors' => [
                    'required' => 'password harus di isi',
                ],
            ],
        ]);

        if (!$validation) {
            return redirect()->to('login')->withInput();
        }

        if ($profileEnabled) {
            $profile['validation'] = $this->profileMs($validationStart);
        }

        $username = (string) $this->request->getPost('username');
        $password = (string) $this->request->getPost('password');

        $model = new UserModel();

        $userQueryStart = microtime(true);

        // Fetch only the user fields required by the authentication flow.
        $user = $model
            ->select('id, username, password, role_id')
            ->where('username', $username)
            ->first();

        if ($profileEnabled) {
            $profile['user_query'] = $this->profileMs($userQueryStart);
        }

        $passwordStart = microtime(true);
        $passwordValid = $user && password_verify($password, $user['password']);

        if ($profileEnabled) {
            $profile['password_verify'] = $this->profileMs($passwordStart);
        }

        if (!$passwordValid) {
            session()->setFlashdata('msg', 'Username atau Password salah');
            return redirect()->to('/login');
        }

        $roleId = (int) $user['role_id'];
        $roleKey = self::ROLE_MAP[$roleId] ?? 'user';
        $namaRole = ucfirst($roleKey);

        $namaPleton = '';
        $namaKompi = '';
        $namaBatalyon = '';
        $namaLengkap = 'User';

        $db = \Config\Database::connect();
        $profileQueryStart = microtime(true);

        // Student login is the hot path for concurrent exam users.
        // Fetch student name and pleton name in one query.
        if ($roleId === 7) {
            $siswa = $db->table('siswa')
                ->select('siswa.nama, pleton.nama_pleton')
                ->join('pleton', 'pleton.id = siswa.pleton_id', 'left')
                ->where('siswa.user_id', $user['id'])
                ->get()
                ->getRow();

            if ($siswa) {
                $namaLengkap = $siswa->nama ?? 'User';

                if (!empty($siswa->nama_pleton)) {
                    $namaPleton = ' - ' . $siswa->nama_pleton;
                }
            }
        } else {
            // Keep the existing staff role behavior.
            if (in_array($roleId, [4, 5, 6], true)) {
                $pegawai = $db->table('pegawai')
                    ->select('id, nama, nomor_induk')
                    ->where('user_id', $user['id'])
                    ->get()
                    ->getRow();

                if ($pegawai) {
                    $namaLengkap = $pegawai->nama ?? 'User';

                    if ($roleId === 4) {
                        $pleton = $db->table('pleton')
                            ->select('nama_pleton')
                            ->groupStart()
                            ->where('danton_id', $pegawai->nomor_induk)
                            ->orWhere('danton_id', $pegawai->id)
                            ->groupEnd()
                            ->get()
                            ->getRow();

                        if ($pleton) {
                            $namaPleton = ' - ' . $pleton->nama_pleton;
                        }
                    } elseif ($roleId === 5) {
                        $kompi = $db->table('kompi')
                            ->select('nama_kompi')
                            ->groupStart()
                            ->where('danki_id', $pegawai->nomor_induk)
                            ->orWhere('danki_id', $pegawai->id)
                            ->groupEnd()
                            ->get()
                            ->getRow();

                        if ($kompi) {
                            $namaKompi = ' - ' . $kompi->nama_kompi;
                        }
                    } elseif ($roleId === 6) {
                        $batalyon = $db->table('batalyon')
                            ->select('nama_batalyon')
                            ->groupStart()
                            ->where('danyon_id', $pegawai->nomor_induk)
                            ->orWhere('danyon_id', $pegawai->id)
                            ->groupEnd()
                            ->get()
                            ->getRow();

                        if ($batalyon) {
                            $namaBatalyon = ' - ' . $batalyon->nama_batalyon;
                        }
                    }
                }
            } elseif ($roleId !== 1) {
                $profileTable = $roleId === 7 ? 'siswa' : 'pegawai';
                $staffProfile = $db->table($profileTable)
                    ->select('nama')
                    ->where('user_id', $user['id'])
                    ->get()
                    ->getRow();

                if ($staffProfile) {
                    $namaLengkap = $staffProfile->nama ?? 'User';
                }
            }
        }

        if ($profileEnabled) {
            $profile['profile_query'] = $this->profileMs($profileQueryStart);
        }

        $sessionStart = microtime(true);

        session()->set([
            'user_id'       => $user['id'],
            'username'      => $user['username'],
            'role_id'       => $roleId,
            'nama_role'     => $namaRole,
            'nama_pleton'   => $namaPleton,
            'nama_kompi'    => $namaKompi,
            'nama_batalyon' => $namaBatalyon,
            'nama'          => $namaLengkap,
            'logged_in'     => true,
        ]);

        if ($profileEnabled) {
            $profile['session'] = $this->profileMs($sessionStart);
            $profile['total'] = $this->profileMs($profileStart);
        }

        $redirectUrl = $roleId === 1
            ? '/dashboard'
            : '/' . $roleKey . '/users/profil';

        $response = redirect()->to($redirectUrl);

        if ($profileEnabled) {
            foreach ($profile as $name => $milliseconds) {
                $response->setHeader(
                    'X-Auth-Profile-' . $name,
                    number_format($milliseconds, 3, '.', '')
                );
            }
        }

        return $response;
    }

    private function profileMs(float $start): float
    {
        return (microtime(true) - $start) * 1000;
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}
