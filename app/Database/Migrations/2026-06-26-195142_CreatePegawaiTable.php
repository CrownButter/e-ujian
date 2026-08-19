<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePegawaiTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nama'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'tipe_pegawai'  => ['type' => 'ENUM', 'constraint' => ['polri', 'pns'], 'default' => 'polri'],
            'nomor_induk'   => ['type' => 'VARCHAR', 'constraint' => 50], // NRP atau NIP
            'pangkat_id'    => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pegawai');
    }

    public function down()
    {
        $this->forge->dropTable('pegawai');
    }
}
