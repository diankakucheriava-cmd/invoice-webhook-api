<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInvoicesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'external_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'customer_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'currency' => [
                'type'       => 'CHAR',
                'constraint' => 3,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'due_date' => [
                'type' => 'DATE',
            ],
            'event_created_at' => [
                'type' => 'DATETIME',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('external_id');
        $this->forge->addKey('customer_id');

        $this->forge->addForeignKey(
            'customer_id',
            'customers',
            'id',
            'CASCADE',
            'SET NULL'
        );

        $this->forge->createTable('invoices');
    }

    public function down()
    {
        $this->forge->dropTable('invoices');
    }
}
