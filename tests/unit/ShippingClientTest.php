<?php

namespace Tests\Unit;

use App\Services\ShippingClient;
use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class ShippingClientTest extends CIUnitTestCase
{
    public function testCreateShipmentReturnsResponseOnSuccess(): void
    {
        $response = new Response(config('App'));

        $response
            ->setStatusCode(200)
            ->setJSON([
                'shipment_id' => 'SHIP-123',
                'status' => 'created',
            ]);

        $client = $this->createMock(CURLRequest::class);

        $client
            ->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $shippingClient = new ShippingClient(
            $client,
            'https://shipping.example'
        );

        $result = $shippingClient->createShipment(
            $this->invoicePayload()
        );

        $this->assertSame('SHIP-123', $result['shipment_id']);
        $this->assertSame('created', $result['status']);
    }

    public function testCreateShipmentRetriesAfterServerErrorAndSucceeds(): void
    {
        $failedResponse = new Response(config('App'));
        $failedResponse->setStatusCode(500);

        $successfulResponse = new Response(config('App'));

        $successfulResponse
            ->setStatusCode(200)
            ->setJSON([
                'shipment_id' => 'SHIP-123',
                'status' => 'created',
            ]);

        $client = $this->createMock(CURLRequest::class);

        $client
            ->expects($this->exactly(2))
            ->method('post')
            ->willReturnOnConsecutiveCalls(
                $failedResponse,
                $successfulResponse
            );

        $shippingClient = new ShippingClient(
            $client,
            'https://shipping.example'
        );

        $result = $shippingClient->createShipment(
            $this->invoicePayload()
        );

        $this->assertSame('SHIP-123', $result['shipment_id']);
        $this->assertSame('created', $result['status']);
    }

    public function testCreateShipmentThrowsExceptionAfterRetryOnHttpError(): void
    {
        $response = new Response(config('App'));
        $response->setStatusCode(500);

        $client = $this->createMock(CURLRequest::class);

        $client
            ->expects($this->exactly(2))
            ->method('post')
            ->willReturn($response);

        $shippingClient = new ShippingClient(
            $client,
            'https://shipping.example'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Shipping API returned HTTP 500'
        );

        $shippingClient->createShipment(
            $this->invoicePayload()
        );
    }

    public function testCreateShipmentDoesNotRetryOnClientError(): void
    {
        $response = new Response(config('App'));
        $response->setStatusCode(400);

        $client = $this->createMock(CURLRequest::class);

        $client
            ->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $shippingClient = new ShippingClient(
            $client,
            'https://shipping.example'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Shipping API returned HTTP 400'
        );

        $shippingClient->createShipment(
            $this->invoicePayload()
        );
    }

    public function testCreateShipmentThrowsExceptionOnInvalidJson(): void
    {
        $response = new Response(config('App'));

        $response
            ->setStatusCode(200)
            ->setBody('not-json');

        $client = $this->createMock(CURLRequest::class);

        $client
            ->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $shippingClient = new ShippingClient(
            $client,
            'https://shipping.example'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Shipping API returned invalid JSON.'
        );

        $shippingClient->createShipment(
            $this->invoicePayload()
        );
    }

    private function invoicePayload(): array
    {
        return [
            'invoice_id' => 'INV-2026-00123',
            'customer' => [
                'id' => 'CUST-4711',
            ],
            'amount' => 249.90,
            'currency' => 'CHF',
        ];
    }
}
