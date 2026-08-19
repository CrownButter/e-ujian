<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProfilesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nama'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'pangkat_id'  => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'null' => true],
            'angkatan_id' => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'null' => true],
            'pleton_id'   => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('id', true);

        // Relasi
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('profiles');
    }

    public function down()
    {
        $this->forge->dropTable('profiles');
    }
}
