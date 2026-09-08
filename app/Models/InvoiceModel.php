<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceModel extends Model
{
    protected $table            = 'invoices';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'external_id',
        'customer_id',
        'amount',
        'currency',
        'status',
        'due_date',
        'event_created_at',
    ];

    protected $useTimestamps = true;
}
