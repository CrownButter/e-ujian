<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => 50], // Bisa diisi NRP atau NIS
            'password' => ['type' => 'VARCHAR', 'constraint' => 255],
            'role_id'  => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true],
        ]);
        $this->forge->addKey('id', true);
        // Menambahkan relasi ke tabel roles
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}
