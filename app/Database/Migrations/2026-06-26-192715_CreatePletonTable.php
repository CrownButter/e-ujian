<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePletonTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'auto_increment' => true],
            'kompi_id'    => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true],
            'nama_pleton' => ['type' => 'VARCHAR', 'constraint' => 100],
            'danton_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        // Menambahkan relasi ke tabel kompi
        $this->forge->addForeignKey('kompi_id', 'kompi', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pleton');
    }

    public function down()
    {
        $this->forge->dropTable('pleton');
    }
}
