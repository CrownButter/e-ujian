<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MasterSeeder extends Seeder
{
    public function run()
    {
        // 1. Isi Roles
        $roles = [
            ['nama_role' => 'Admin'],
            ['nama_role' => 'Operator'],
            ['nama_role' => 'Pengasuh'],
            ['nama_role' => 'Danton'],
            ['nama_role' => 'Danki'],
            ['nama_role' => 'Danyon'],
            ['nama_role' => 'Siswa'],
        ];
        $this->db->table('roles')->insertBatch($roles);

        // 2. Isi Pangkat
        $pangkat = [
            ['nama_pangkat' => 'Bripda'],
            ['nama_pangkat' => 'Briptu'],
            ['nama_pangkat' => 'Bripka'],
            ['nama_pangkat' => 'Aipda'],
            ['nama_pangkat' => 'Aiptu'],
            ['nama_pangkat' => 'Ipda'],
            ['nama_pangkat' => 'Iptu'],
            ['nama_pangkat' => 'AKP'],
            ['nama_pangkat' => 'Kompol'],
            ['nama_pangkat' => 'AKBP'],
            ['nama_pangkat' => 'KOMBES'],
        ];
        $this->db->table('pangkat')->insertBatch($pangkat);
    }
}
