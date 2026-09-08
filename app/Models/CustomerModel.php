<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table            = 'customers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'external_id',
        'name',
        'email',
    ];

    protected $useTimestamps = true;

    public function upsertFromWebhook(array $customer): int
    {
        $existingCustomer = $this
            ->where('external_id', $customer['id'])
            ->first();

        if ($existingCustomer !== null) {
            $this->update($existingCustomer['id'], [
                'name' => $customer['name'],
                'email' => $customer['email'],
            ]);

            return (int) $existingCustomer['id'];
        }

        return (int) $this->insert([
            'external_id' => $customer['id'],
            'name' => $customer['name'],
            'email' => $customer['email'],
        ], true);
    }
}
