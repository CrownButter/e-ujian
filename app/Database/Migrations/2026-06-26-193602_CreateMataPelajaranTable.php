<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMataPelajaranTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'auto_increment' => true],
            'nama_mapel'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'kode_mapel'  => ['type' => 'VARCHAR', 'constraint' => 20],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('mata_pelajaran');
    }

    public function down()
    {
        $this->forge->dropTable('mata_pelajaran');
    }
}
