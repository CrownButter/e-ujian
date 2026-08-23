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

    private function getNamaLengkap($userId, $roleId)
    {
        $db = \Config\Database::connect();

        if ($roleId == 7) {
            $table = 'siswa';
        } else {
            // Untuk role 1-6, kita arahkan ke tabel pegawai
            $table = 'pegawai';
        }

        // Ambil data berdasarkan tabel dan pastikan mencocokkan user_id secara spesifik
        $data = $db->table($table)->where('user_id', $userId)->get()->getRow();

        // Jika data tidak ditemukan, gunakan default 'User'
        return $data ? $data->nama : 'User';
    }


    public function auth()
    {
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

        $model    = new UserModel();
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $data = $model->where('username', $username)->first();

        if ($data && password_verify($password, $data['password'])) {

            // 1. Ambil Nama Role
            $roleModel = new \App\Models\RoleModel();
            $roleData  = $roleModel->find($data['role_id']);
            $namaRole = $roleData ? $roleData['nama_role'] : 'User';

            // 2. Logika Tambahan untuk Danton, Danki, dan Danyon
            $nama_pleton = '';
            $nama_kompi = '';
            $nama_batalyon = '';
            $db = \Config\Database::connect();

            // Jika Danton (role_id 4)
            if ($data['role_id'] == 4) {
                $pegawai = $db->table('pegawai')->where('user_id', $data['id'])->get()->getRow();
                if ($pegawai) {
                    $pleton = $db->table('pleton')->where('danton_id', $pegawai->nomor_induk)->orWhere('danton_id', $pegawai->id)->get()->getRow();
                    if ($pleton) {
                        $nama_pleton = ' - ' . $pleton->nama_pleton;
                        // Simpan ke session juga jika diperlukan
                        session()->set('nama_pleton', $pleton->nama_pleton);
                    }
                }
            }
            // Jika Danki (role_id 5)
            elseif ($data['role_id'] == 5) {
                $pegawai = $db->table('pegawai')->where('user_id', $data['id'])->get()->getRow();
                if ($pegawai) {
                    $kompi = $db->table('kompi')->where('danki_id', $pegawai->nomor_induk)->orWhere('danki_id', $pegawai->id)->get()->getRow();
                    if ($kompi) {
                        $nama_kompi = ' - ' . $kompi->nama_kompi;
                        session()->set('nama_kompi', $kompi->nama_kompi);
                    }
                }
            }
            // Jika Danyon (role_id 6)
            elseif ($data['role_id'] == 6) {
                $pegawai = $db->table('pegawai')->where('user_id', $data['id'])->get()->getRow();
                if ($pegawai) {
                    $batalyon = $db->table('batalyon')->where('danyon_id', $pegawai->nomor_induk)->orWhere('danyon_id', $pegawai->id)->get()->getRow();
                    if ($batalyon) {
                        $nama_batalyon = ' - ' . $batalyon->nama_batalyon;
                        session()->set('nama_batalyon', $batalyon->nama_batalyon);
                    }
                }
            }
            // Jika Siswa (role_id 7) -> TAMBAHAN DI SINI
            elseif ($data['role_id'] == 7) {
                $siswa = $db->table('siswa')->where('user_id', $data['id'])->get()->getRow();
                if ($siswa && $siswa->pleton_id) {
                    $pleton = $db->table('pleton')->where('id', $siswa->pleton_id)->get()->getRow();
                    if ($pleton) {
                        $nama_pleton = ' - ' . $pleton->nama_pleton;
                        session()->set('nama_pleton', $pleton->nama_pleton);
                    }
                }
            }

            // 3. Set session dengan data yang lengkap
            session()->set([
                'user_id'       => $data['id'],
                'username'      => $data['username'],
                'role_id'       => $data['role_id'],
                'nama_role'     => $namaRole,
                'nama_pleton'   => $nama_pleton,
                'nama_kompi'    => $nama_kompi,     // Disimpan untuk Danki
                'nama_batalyon' => $nama_batalyon, // Disimpan untuk Danyon
                'nama'          => $this->getNamaLengkap($data['id'], $data['role_id']),
                'logged_in'     => true
            ]);

            // Logika Pengalihan Berdasarkan Role
            if ($data['role_id'] == 7) {
                return redirect()->to('/siswa/users/profil');
            } else {
                return redirect()->to('/dashboard');
            }
        }

        // 4. Jika login gagal
        session()->setFlashdata('msg', 'Username atau Password salah');
        return redirect()->to('/login');
    }
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}
