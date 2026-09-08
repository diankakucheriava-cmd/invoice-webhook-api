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
}
