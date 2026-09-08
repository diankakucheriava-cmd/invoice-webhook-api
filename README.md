# Invoice Webhook API

Small CodeIgniter 4 API for processing invoice webhooks.

The endpoint validates an incoming invoice event, stores the customer, invoice and webhook event in MySQL, and sends the invoice data to a mock shipping API.

## Tech Stack

- PHP 8.3
- CodeIgniter 4
- MySQL 8
- Docker / Docker Compose
- PHPUnit

## Quick Start

Copy the environment template:

```bash
cp env .env
```

Set `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `database.default.password` and `database.tests.password` in `.env`.

Then start the application, run the migrations and tests:

```bash
docker compose up -d --build
docker compose exec app php spark migrate
docker compose exec app vendor/bin/phpunit
```

```
The API is available at:

```text
http://localhost:8080
```

## Webhook Endpoint

```http
POST /api/webhooks/invoice
Content-Type: application/json
```

Example payload:

```json
{
  "event": "invoice.created",
  "invoice_id": "INV-2026-00123",
  "customer": {
    "id": "CUST-4711",
    "name": "Muster Optik GmbH",
    "email": "kontakt@musteroptik.example"
  },
  "amount": 249.90,
  "currency": "CHF",
  "status": "open",
  "due_date": "2026-10-15",
  "created_at": "2026-09-08T10:15:00Z"
}
```

Successful response:

```json
{
  "status": "processed",
  "customer_id": 1,
  "invoice_id": 1,
  "webhook_event_id": 1
}
```

Malformed JSON returns `400 Bad Request`. Invalid webhook data returns `422 Unprocessable Entity` with validation errors.

## Implementation

The application uses two main services:

- `InvoiceWebhookService` orchestrates webhook processing, database persistence, transactions and the shipping call.
- `ShippingClient` handles communication with the external shipping API, including timeout, HTTP errors and a retry for transient server or network failures.

Validation rules are defined separately in the CodeIgniter validation configuration, keeping the controller focused on request/response handling.

Customer, invoice and webhook event writes are wrapped in a database transaction to prevent partially persisted data.

The shipping request is made after the transaction is committed, so an unavailable external service does not keep the database transaction open. Failed shipping requests are recorded by marking the webhook event as `failed`.

Duplicate webhooks are protected by both an application-level idempotency check and a database unique constraint on:

```text
(event_type, external_invoice_id)
```

## Database

```text
customers
  └── id, external_id (unique), name, email

invoices
  └── id, external_id (unique), customer_id, amount,
      currency, status, due_date, event_created_at

webhook_events
  └── id, event_type, invoice_id, external_invoice_id,
      payload (JSON), status, error_message, processed_at
```

Relations:

```text
customers  1 ─── *  invoices  1 ─── *  webhook_events
```

If a customer is deleted, `invoices.customer_id` is set to `NULL`, preserving invoice history.

An invoice referenced by a webhook event cannot be deleted (`ON DELETE RESTRICT`) to protect financial/audit data.

The original webhook payload is stored as JSON for traceability.

## Tests

The test suite covers the main flows, including webhook validation, successful processing, duplicate protection, customer updates, shipping failures and retry behavior.

Database integration tests use a separate `webhook_service_test` database, which is automatically created by the MySQL Docker initialization script.

Run the test suite:

```bash
docker compose exec app vendor/bin/phpunit
```

PHPUnit may show `No code coverage driver available`; code coverage is not required to run the test suite.