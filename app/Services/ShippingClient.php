<?php

namespace App\Services;

use CodeIgniter\HTTP\CURLRequest;
use RuntimeException;

class ShippingClient
{
    private CURLRequest $client;
    private string $baseUrl;

    public function __construct(
        ?CURLRequest $client = null,
        ?string $baseUrl = null
    ) {
        $this->client = $client ?? service('curlrequest');

        $this->baseUrl = $baseUrl
            ?? env('shipping.baseUrl', 'https://httpbin.org');
    }

    public function createShipment(array $invoice): array
    {
        try {
            $response = $this->client->post(
                $this->baseUrl . '/post',
                [
                    'json' => [
                        'invoice_id' => $invoice['invoice_id'],
                        'customer_id' => $invoice['customer']['id'],
                        'amount' => $invoice['amount'],
                        'currency' => $invoice['currency'],
                    ],
                    'timeout' => 5,
                    'http_errors' => false,
                ]
            );
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'Shipping API request failed: ' . $exception->getMessage(),
                0,
                $exception
            );
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException(
                'Shipping API returned HTTP ' . $statusCode
            );
        }

        $body = json_decode(
            $response->getBody(),
            true
        );

        if (! is_array($body)) {
            throw new RuntimeException(
                'Shipping API returned invalid JSON.'
            );
        }

        return $body;
    }
}
