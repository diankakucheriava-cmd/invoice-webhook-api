<?php

namespace App\Services;

use App\Models\CustomerModel;
use App\Models\InvoiceModel;
use App\Models\WebhookEventModel;
use CodeIgniter\Database\BaseConnection;

class InvoiceWebhookService
{
    private CustomerModel $customerModel;
    private InvoiceModel $invoiceModel;
    private WebhookEventModel $webhookEventModel;
    private ShippingClient $shippingClient;
    private BaseConnection $db;

    public function __construct(
        ?CustomerModel $customerModel = null,
        ?InvoiceModel $invoiceModel = null,
        ?WebhookEventModel $webhookEventModel = null,
        ?ShippingClient $shippingClient = null,
        ?BaseConnection $db = null
    ) {
        $this->customerModel = $customerModel ?? new CustomerModel();
        $this->invoiceModel = $invoiceModel ?? new InvoiceModel();
        $this->webhookEventModel = $webhookEventModel ?? new WebhookEventModel();
        $this->shippingClient = $shippingClient ?? new ShippingClient();
        $this->db = $db ?? db_connect();
    }

    public function handle(array $payload): array
    {
        $existingEvent = $this->findExistingEvent(
            $payload['event'],
            $payload['invoice_id']
        );

        if ($existingEvent !== null) {
            return [
                'status' => 'already_processed',
                'invoice_id' => $payload['invoice_id'],
            ];
        }

        $this->db->transBegin();

        try {
            $customerId = $this->saveCustomer($payload['customer']);

            $invoiceId = $this->createInvoice(
                $payload,
                $customerId
            );

            $webhookEventId = $this->createWebhookEvent(
                $payload,
                $invoiceId
            );

            $this->db->transCommit();
        } catch (\Throwable $exception) {
            $this->db->transRollback();

            throw $exception;
        }

        try {
            $this->shippingClient->createShipment([
                'invoice_id' => $payload['invoice_id'],
                'customer' => [
                    'id' => $payload['customer']['id'],
                ],
                'amount' => $payload['amount'],
                'currency' => $payload['currency'],
            ]);

            $this->markWebhookProcessed($webhookEventId);

            return [
                'status' => 'processed',
                'customer_id' => $customerId,
                'invoice_id' => $invoiceId,
                'webhook_event_id' => $webhookEventId,
            ];
        } catch (\Throwable $exception) {
            $this->markWebhookFailed(
                $webhookEventId,
                $exception->getMessage()
            );

            return [
                'status' => 'failed',
                'shipping_status' => 'failed',
                'customer_id' => $customerId,
                'invoice_id' => $invoiceId,
                'webhook_event_id' => $webhookEventId,
            ];
        }
    }

    private function findExistingEvent(
        string $eventType,
        string $externalInvoiceId
    ): ?array {
        return $this->webhookEventModel
            ->where('event_type', $eventType)
            ->where('external_invoice_id', $externalInvoiceId)
            ->first();
    }

    private function saveCustomer(array $customer): int
    {
        $existingCustomer = $this->customerModel
            ->where('external_id', $customer['id'])
            ->first();

        if ($existingCustomer !== null) {
            $this->customerModel->update($existingCustomer['id'], [
                'name' => $customer['name'],
                'email' => $customer['email'],
            ]);

            return (int) $existingCustomer['id'];
        }

        return (int) $this->customerModel->insert([
            'external_id' => $customer['id'],
            'name' => $customer['name'],
            'email' => $customer['email'],
        ], true);
    }

    private function createInvoice(array $payload, int $customerId): int
    {
        $eventCreatedAt = new \DateTimeImmutable($payload['created_at']);

        return (int) $this->invoiceModel->insert([
            'external_id' => $payload['invoice_id'],
            'customer_id' => $customerId,
            'amount' => $payload['amount'],
            'currency' => $payload['currency'],
            'status' => $payload['status'],
            'due_date' => $payload['due_date'],
            'event_created_at' => $eventCreatedAt->format('Y-m-d H:i:s'),
        ], true);
    }

    private function createWebhookEvent(array $payload, int $invoiceId): int
    {
        return (int) $this->webhookEventModel->insert([
            'event_type' => $payload['event'],
            'invoice_id' => $invoiceId,
            'external_invoice_id' => $payload['invoice_id'],
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'status' => 'received',
            'error_message' => null,
            'processed_at' => null,
        ], true);
    }

    private function markWebhookProcessed(int $webhookEventId): void
    {
        $this->webhookEventModel->update($webhookEventId, [
            'status' => 'processed',
            'processed_at' => date('Y-m-d H:i:s'),
            'error_message' => null,
        ]);
    }

    private function markWebhookFailed(
        int $webhookEventId,
        string $errorMessage
    ): void {
        $this->webhookEventModel->update($webhookEventId, [
            'status' => 'failed',
            'error_message' => $errorMessage,
            'processed_at' => null,
        ]);
    }
}
