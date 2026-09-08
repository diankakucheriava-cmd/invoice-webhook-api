<?php

namespace App\Services;

use CodeIgniter\HTTP\CURLRequest;
use RuntimeException;

class ShippingClient
{
    private const MAX_ATTEMPTS = 2;
    private const RETRY_DELAY_MICROSECONDS = 200_000;

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
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
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
                if ($attempt < self::MAX_ATTEMPTS) {
                    usleep(self::RETRY_DELAY_MICROSECONDS);
                    continue;
                }

                throw new RuntimeException(
                    'Shipping API request failed: ' . $exception->getMessage(),
                    0,
                    $exception
                );
            }

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                return $this->parseResponse($response->getBody());
            }

            if (
                $statusCode >= 500
                && $attempt < self::MAX_ATTEMPTS
            ) {
                usleep(self::RETRY_DELAY_MICROSECONDS);
                continue;
            }

            throw new RuntimeException(
                'Shipping API returned HTTP ' . $statusCode
            );
        }

        throw new RuntimeException(
            'Shipping API request failed.'
        );
    }

    private function parseResponse(string $body): array
    {
        $data = json_decode($body, true);

        if (! is_array($data)) {
            throw new RuntimeException(
                'Shipping API returned invalid JSON.'
            );
        }

        return $data;
    }
}
