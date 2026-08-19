<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        // 1. Data untuk tabel users
        $userData = [
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role_id'  => 1, 
        ];

        $this->db->table('users')->insert($userData);

        // 2. Ambil ID dari user yang baru saja diinsert
        $userId = $this->db->insertID();

        // 3. Masukkan data ke tabel pegawai (atau tabel lain sesuai role)
        $pegawaiData = [
            'user_id'     => $userId,
            'nama'        => 'Administrator Utama',
            'tipe_pegawai'=> 'pns', // Sesuaikan dengan enum di DB
            'nomor_induk' => '00000000',
        ];

        $this->db->table('pegawai')->insert($pegawaiData);
    }
}