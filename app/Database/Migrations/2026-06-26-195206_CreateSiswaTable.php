<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSiswaTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nama'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'nosis'       => ['type' => 'VARCHAR', 'constraint' => 50], // Nomor Induk Siswa
            'pleton_id'   => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'null' => true],
            'angkatan_id' => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('pleton_id', 'pleton', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('siswa');
    }

    public function down()
    {
        $this->forge->dropTable('siswa');
    }
}
