<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class WebhookController extends BaseController
{
    public function handle(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);

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

        // TO DO

        return $this->response->setJSON([
            'message' => 'Webhook received.',
            'data'    => $validated,
        ]);
    }
}
