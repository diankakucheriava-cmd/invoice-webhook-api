<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWebhookEventsTable extends Migration
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
            'event_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'invoice_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'external_invoice_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'payload' => [
                'type' => 'JSON',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'processed_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addKey('invoice_id');

        $this->forge->addUniqueKey(
            ['event_type', 'external_invoice_id']
        );

        $this->forge->addForeignKey(
            'invoice_id',
            'invoices',
            'id',
            'CASCADE',
            'RESTRICT'
        );

        $this->forge->createTable('webhook_events');
    }

    public function down()
    {
        $this->forge->dropTable('webhook_events');
    }
}
