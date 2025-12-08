<?php

namespace App\Controllers\v1;

use Anser\Gateway\Worker\GatewayWorker; // Assume existing class or wrapper
use App\Controllers\BaseController;
use PhpAmqpLib\Message\AMQPMessage;

class OrderController extends BaseController
{
    private $mqChannel; // Injected via DI

    public function create()
    {
        // 1. Validation (Reuse Anser-Gateway Filters)
        
        // 2. Wrap Request
        $requestId = uniqid('req_', true);
        $payload = [
            'action' => 'create_order',
            'data'   => $this->request->getJSON(true),
            'meta'   => [
                'ip' => $this->request->getIPAddress(),
                'timestamp' => time()
            ]
        ];

        // 3. Enqueue
        $msg = new AMQPMessage(json_encode($payload), [
            'correlation_id' => $requestId,
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);

        $this->mqChannel->basic_publish($msg, '', 'anser_incoming_request_queue');

        // 4. Respond Immediately
        return $this->response->setJSON([
            'status' => 202,
            'message' => 'Order request accepted',
            'transaction_id' => $requestId
        ])->setStatusCode(202);
    }
}