<?php

namespace Tests\Unit;

use App\Models\CustomerModel;
use App\Models\InvoiceModel;
use App\Models\WebhookEventModel;
use App\Services\InvoiceWebhookService;
use App\Services\ShippingClient;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use RuntimeException;

class InvoiceWebhookServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';

    protected $refresh = true;
    protected $migrate = true;

    public function testWebhookIsProcessedSuccessfully(): void
    {
        $shippingClient = $this->createMock(ShippingClient::class);

        $shippingClient
            ->expects($this->once())
            ->method('createShipment')
            ->willReturn([
                'shipment_id' => 'SHIP-123',
            ]);

        $service = new InvoiceWebhookService(
            shippingClient: $shippingClient
        );

        $result = $service->handle($this->payload());

        $this->assertSame('processed', $result['status']);

        $this->seeInDatabase('customers', [
            'external_id' => 'CUST-4711',
            'name' => 'Muster Optik GmbH',
        ]);

        $this->seeInDatabase('invoices', [
            'external_id' => 'INV-2026-00123',
            'status' => 'open',
        ]);

        $this->seeInDatabase('webhook_events', [
            'event_type' => 'invoice.created',
            'external_invoice_id' => 'INV-2026-00123',
            'status' => 'processed',
            'error_message' => null,
        ]);
    }

    public function testShippingFailureKeepsInvoiceAndMarksWebhookAsFailed(): void
    {
        $shippingClient = $this->createMock(ShippingClient::class);

        $shippingClient
            ->expects($this->once())
            ->method('createShipment')
            ->willThrowException(
                new RuntimeException('Shipping API request timed out')
            );

        $service = new InvoiceWebhookService(
            shippingClient: $shippingClient
        );

        $result = $service->handle($this->payload());

        $this->assertSame('failed', $result['status']);
        $this->assertSame('failed', $result['shipping_status']);

        $this->seeInDatabase('invoices', [
            'external_id' => 'INV-2026-00123',
        ]);

        $this->seeInDatabase('webhook_events', [
            'external_invoice_id' => 'INV-2026-00123',
            'status' => 'failed',
            'error_message' => 'Shipping API request timed out',
        ]);
    }

    public function testDuplicateWebhookDoesNotCreateDuplicateInvoiceOrCallShippingAgain(): void
    {
        $shippingClient = $this->createMock(ShippingClient::class);

        $shippingClient
            ->expects($this->once())
            ->method('createShipment')
            ->willReturn([
                'shipment_id' => 'SHIP-123',
            ]);

        $service = new InvoiceWebhookService(
            shippingClient: $shippingClient
        );

        $firstResult = $service->handle($this->payload());
        $secondResult = $service->handle($this->payload());

        $this->assertSame('processed', $firstResult['status']);
        $this->assertSame('already_processed', $secondResult['status']);

        $this->assertSame(
            1,
            (new InvoiceModel())
                ->where('external_id', 'INV-2026-00123')
                ->countAllResults()
        );

        $this->assertSame(
            1,
            (new WebhookEventModel())
                ->where('event_type', 'invoice.created')
                ->where('external_invoice_id', 'INV-2026-00123')
                ->countAllResults()
        );
    }

    public function testExistingCustomerIsUpdatedInsteadOfDuplicated(): void
    {
        $customerModel = new CustomerModel();

        $customerId = $customerModel->insert([
            'external_id' => 'CUST-4711',
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ], true);

        $shippingClient = $this->createMock(ShippingClient::class);

        $shippingClient
            ->expects($this->once())
            ->method('createShipment')
            ->willReturn([
                'shipment_id' => 'SHIP-123',
            ]);

        $service = new InvoiceWebhookService(
            shippingClient: $shippingClient
        );

        $result = $service->handle($this->payload());

        $this->assertSame('processed', $result['status']);

        $this->assertSame(
            1,
            $customerModel
                ->where('external_id', 'CUST-4711')
                ->countAllResults()
        );

        $customer = $customerModel->find($customerId);

        $this->assertSame(
            'Muster Optik GmbH',
            $customer['name']
        );

        $this->assertSame(
            'kontakt@musteroptik.example',
            $customer['email']
        );
    }

    private function payload(): array
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
