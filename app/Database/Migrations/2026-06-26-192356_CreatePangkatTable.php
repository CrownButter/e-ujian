<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePangkatTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'auto_increment' => true],
            'nama_pangkat' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('pangkat');
    }

    public function down()
    {
        $this->forge->dropTable('pangkat');
    }
}
