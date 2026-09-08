<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------
    public array $invoiceWebhook = [
        'event' => [
            'label' => 'Event',
            'rules' => 'required|max_length[100]',
        ],
        'invoice_id' => [
            'label' => 'Invoice ID',
            'rules' => 'required|max_length[100]',
        ],
        'customer.id' => [
            'label' => 'Customer ID',
            'rules' => 'required|max_length[100]',
        ],
        'customer.name' => [
            'label' => 'Customer name',
            'rules' => 'required|max_length[255]',
        ],
        'customer.email' => [
            'label' => 'Customer email',
            'rules' => 'required|valid_email|max_length[255]',
        ],
        'amount' => [
            'label' => 'Amount',
            'rules' => 'required|decimal|greater_than_equal_to[0]',
        ],
        'currency' => [
            'label' => 'Currency',
            'rules' => 'required|exact_length[3]|alpha',
        ],
        'status' => [
            'label' => 'Status',
            'rules' => 'required|max_length[30]',
        ],
        'due_date' => [
            'label' => 'Due date',
            'rules' => 'required|valid_date[Y-m-d]',
        ],
        'created_at' => [
            'label' => 'Created at',
            'rules' => 'required|valid_date',
        ],
    ];
}
