<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['username', 'password', 'role_id'];

    // Fungsi untuk mendapatkan data user beserta nama rolenya
    public function getUserWithRole()
    {
        $builder = $this->db->table('users');
        $builder->select('users.*, roles.nama_role, pegawai.nama as nama_pegawai, siswa.nama as nama_siswa');
        $builder->join('roles', 'roles.id = users.role_id', 'left');
        // Join ke tabel pegawai dan siswa untuk mengambil nama
        $builder->join('pegawai', 'pegawai.user_id = users.id', 'left');
        $builder->join('siswa', 'siswa.user_id = users.id', 'left');

        return $builder->get()->getResultArray();
    }

    // Di dalam UserModel.php
    public function createUserWithProfile($data, $profileData, $role)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Simpan ke users
        $this->save($data);
        $userId = $this->insertID();

        // 2. Simpan ke tabel terkait (pegawai atau siswa)
        if ($role == 7) { // ID 7 = Siswa
            $db->table('siswa')->insert(array_merge($profileData, ['user_id' => $userId]));
        } else { // Asumsi role lain adalah pegawai
            $db->table('pegawai')->insert(array_merge($profileData, ['user_id' => $userId]));
        }

        $db->transComplete();
        return $db->transStatus();
    }

    // Tambahkan di App\Models\UserModel
    public function getUserById($id)
    {
        $builder = $this->db->table('users');
        $builder->select('users.*, roles.nama_role, pegawai.nama as nama_pegawai, siswa.nama as nama_siswa, nosis, nomor_induk,foto');
        $builder->join('roles', 'roles.id = users.role_id', 'left');
        $builder->join('pegawai', 'pegawai.user_id = users.id', 'left');
        $builder->join('pangkat', 'pangkat.id = pegawai.pangkat_id', 'left');
        $builder->join('siswa', 'siswa.user_id = users.id', 'left');
        $builder->where('users.id', $id);

        return $builder->get()->getRowArray(); // Ambil satu baris saja
    }
}
