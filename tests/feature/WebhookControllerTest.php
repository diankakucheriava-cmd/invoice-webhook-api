<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class WebhookControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testWebhookRejectsInvalidJson(): void
    {
        $result = $this
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->withBody('{invalid-json')
            ->post('api/webhooks/invoice');

        $result->assertStatus(400);

        $result->assertJSONFragment([
            'message' => 'Invalid JSON payload.',
        ]);
    }

    public function testWebhookRejectsMissingRequiredFields(): void
    {
        $result = $this
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->withBody(json_encode([
                'event' => 'invoice.created',
            ]))
            ->post('api/webhooks/invoice');

        $result->assertStatus(422);

        $result->assertJSONFragment([
            'message' => 'Validation failed.',
        ]);

        $response = json_decode(
            $result->getJSON(),
            true
        );

        $this->assertArrayHasKey('invoice_id', $response['errors']);
        $this->assertArrayHasKey('customer.id', $response['errors']);
        $this->assertArrayHasKey('amount', $response['errors']);
    }

    public function testWebhookRejectsInvalidEmailAndNegativeAmount(): void
    {
        $payload = $this->validPayload();

        $payload['customer']['email'] = 'invalid-email';
        $payload['amount'] = -10;

        $result = $this
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->withBody(json_encode($payload))
            ->post('api/webhooks/invoice');

        $result->assertStatus(422);

        $response = json_decode(
            $result->getJSON(),
            true
        );

        $this->assertArrayHasKey(
            'customer.email',
            $response['errors']
        );

        $this->assertArrayHasKey(
            'amount',
            $response['errors']
        );
    }

    private function validPayload(): array
    {
        return [
            'event' => 'invoice.created',
            'invoice_id' => 'INV-2026-00123',
            'customer' => [
                'id' => 'CUST-4711',
                'name' => 'Muster Optik GmbH',
                'email' => 'kontakt@musteroptik.example',
            ],
            'amount' => 249.90,
            'currency' => 'CHF',
            'status' => 'open',
            'due_date' => '2026-10-15',
            'created_at' => '2026-09-08T10:15:00Z',
        ];
    }
}
