<?php

namespace App\Models;

use CodeIgniter\Model;

class WebhookEventModel extends Model
{
    protected $table            = 'webhook_events';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'event_type',
        'invoice_id',
        'external_invoice_id',
        'payload',
        'status',
        'error_message',
        'processed_at',
    ];

    protected $useTimestamps = true;

    public function findByEventAndInvoice(
        string $eventType,
        string $externalInvoiceId
    ): ?array {
        return $this
            ->where('event_type', $eventType)
            ->where('external_invoice_id', $externalInvoiceId)
            ->first();
    }

    public function markProcessed(int $id): void
    {
        $this->update($id, [
            'status' => 'processed',
            'processed_at' => date('Y-m-d H:i:s'),
            'error_message' => null,
        ]);
    }

    public function markFailed(
        int $id,
        string $errorMessage
    ): void {
        $this->update($id, [
            'status' => 'failed',
            'error_message' => $errorMessage,
            'processed_at' => null,
        ]);
    }
}
