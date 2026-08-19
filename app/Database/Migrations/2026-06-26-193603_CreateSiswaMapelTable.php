<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSiswaMapelTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'siswa_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'mapel_id' => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true],
            'nilai'    => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
        ]);
        
        $this->forge->addKey('id', true);
        
        // Relasi ke tabel siswa dan mata_pelajaran
        $this->forge->addForeignKey('siswa_id', 'siswa', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('mapel_id', 'mata_pelajaran', 'id', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('siswa_mapel');
    }

    public function down()
    {
        $this->forge->dropTable('siswa_mapel');
    }
}