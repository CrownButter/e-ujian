<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAngkatanTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'auto_increment' => true],
            'tahun_angkatan'  => ['type' => 'VARCHAR', 'constraint' => 10],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('angkatan');
    }

    public function down()
    {
        $this->forge->dropTable('angkatan');
    }
}
