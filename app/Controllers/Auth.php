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

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $db = \Config\Database::connect();

        // 1 QUERY GABUNGAN: user + role + profil pegawai/siswa sekaligus.
        // Sebelumnya ini 3-5 query terpisah (UserModel, RoleModel, pegawai/siswa,
        // lalu getNamaLengkap() query ulang tabel yang sama).
        $data = $db->table('users')
            ->select('
                users.id, users.username, users.password, users.role_id,
                roles.nama_role,
                pegawai.id as pegawai_id, pegawai.nama as nama_pegawai, pegawai.nomor_induk as pegawai_nomor_induk,
                siswa.nama as nama_siswa, siswa.pleton_id as siswa_pleton_id
            ')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->join('pegawai', 'pegawai.user_id = users.id', 'left')
            ->join('siswa', 'siswa.user_id = users.id', 'left')
            ->where('users.username', $username)
            ->get()
            ->getRowArray();

        // password_verify dulu (CPU-bound, gak butuh DB), baru cek data user.
        // Urutan gak masalah untuk timing attack di sini karena tetap constant-ish,
        // tapi cek $data dulu untuk hindari null-password error.
        if (!$data || !password_verify($password, $data['password'])) {
            session()->setFlashdata('msg', 'Username atau Password salah');
            return redirect()->to('/login');
        }

        $namaRole = $data['nama_role'] ?? 'User';
        $nama = ($data['role_id'] == 7)
            ? ($data['nama_siswa'] ?? 'User')
            : ($data['nama_pegawai'] ?? 'User');

        $nama_pleton = '';
        $nama_kompi = '';
        $nama_batalyon = '';

        // Query kedua (opsional) HANYA untuk role yang butuh data unit,
        // dan hanya 1 query (bukan 2 seperti sebelumnya: pegawai lookup + unit lookup).
        $unitTableByRole = [
            4 => ['table' => 'pleton',    'col' => 'danton_id', 'nameCol' => 'nama_pleton',   'var' => 'nama_pleton'],
            5 => ['table' => 'kompi',     'col' => 'danki_id',  'nameCol' => 'nama_kompi',    'var' => 'nama_kompi'],
            6 => ['table' => 'batalyon',  'col' => 'danyon_id', 'nameCol' => 'nama_batalyon', 'var' => 'nama_batalyon'],
        ];

        if (isset($unitTableByRole[$data['role_id']]) && $data['pegawai_id']) {
            $u = $unitTableByRole[$data['role_id']];
            $row = $db->table($u['table'])
                ->where($u['col'], $data['pegawai_nomor_induk'])
                ->orWhere($u['col'], $data['pegawai_id'])
                ->get()
                ->getRow();

            if ($row) {
                $value = ' - ' . $row->{$u['nameCol']};
                if ($u['var'] === 'nama_pleton') $nama_pleton = $value;
                if ($u['var'] === 'nama_kompi') $nama_kompi = $value;
                if ($u['var'] === 'nama_batalyon') $nama_batalyon = $value;
                session()->set($u['var'], $row->{$u['nameCol']});
            }
        } elseif ($data['role_id'] == 7 && $data['siswa_pleton_id']) {
            // pleton_id sudah didapat dari query gabungan di atas, tinggal 1 query lookup nama pleton
            $pleton = $db->table('pleton')->where('id', $data['siswa_pleton_id'])->get()->getRow();
            if ($pleton) {
                $nama_pleton = ' - ' . $pleton->nama_pleton;
                session()->set('nama_pleton', $pleton->nama_pleton);
            }
        }

        session()->set([
            'user_id'       => $data['id'],
            'username'      => $data['username'],
            'role_id'       => $data['role_id'],
            'nama_role'     => $namaRole,
            'nama_pleton'   => $nama_pleton,
            'nama_kompi'    => $nama_kompi,
            'nama_batalyon' => $nama_batalyon,
            'nama'          => $nama,
            'logged_in'     => true
        ]);

        if ($data['role_id'] == 7) {
            return redirect()->to('/siswa/users/profil');
        }
        return redirect()->to('/dashboard');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}