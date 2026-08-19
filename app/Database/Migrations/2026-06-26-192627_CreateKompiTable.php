<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKompiTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'auto_increment' => true],
            'batalyon_id' => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true],
            'nama_kompi'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'danki_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        // Menambahkan relasi ke tabel batalyon
        $this->forge->addForeignKey('batalyon_id', 'batalyon', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('kompi');
    }

    public function down()
    {
        $this->forge->dropTable('kompi');
    }
}
