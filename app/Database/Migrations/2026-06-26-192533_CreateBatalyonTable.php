<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBatalyonTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'auto_increment' => true],
            'nama_batalyon' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('batalyon');
    }

    public function down()
    {
        $this->forge->dropTable('batalyon');
    }
}
