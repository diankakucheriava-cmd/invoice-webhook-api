<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\InvoiceWebhookService;

class WebhookController extends BaseController
{
    private InvoiceWebhookService $service;

    public function __construct(?InvoiceWebhookService $service = null)
    {
        $this->service = $service ?? new InvoiceWebhookService();
    }

    public function handle(): ResponseInterface
    {
        try {
            $payload = $this->request->getJSON(true);
        } catch (\Throwable $exception) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'message' => 'Invalid JSON payload.',
                ]);
        }

        if (! is_array($payload)) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON([
                    'message' => 'Invalid JSON payload.',
                ]);
        }

        $validation = service('validation');

        if (! $validation->run($payload, 'invoiceWebhook')) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)
                ->setJSON([
                    'message' => 'Validation failed.',
                    'errors'  => $validation->getErrors(),
                ]);
        }

        $validated = $validation->getValidated();

        $result = $this->service->handle($validated);

        return $this->response->setJSON($result);
    }
}
