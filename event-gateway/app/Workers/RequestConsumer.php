<?php

require_once __DIR__ . '/../../vendor/autoload.php'; // 修正 autoload路徑

use PhpAmqpLib\Connection\AMQPStreamConnection;
use App\Orchestrators\CreateOrderOrchestrator; 

// 1. 連接 RabbitMQ
$connection = new AMQPStreamConnection('rabbitmq', 5672, 'user', 'password');
$channel = $connection->channel();

$channel->queue_declare('order_queue', false, true, false, false);

echo " [*] Worker started. Waiting for messages...\n";

// 2. 定義回調函數
$callback = function ($msg) {
    echo ' [x] Received ', $msg->body, "\n";
    
    $payload = json_decode($msg->body, true);
    $traceId = $payload['id'] ?? 'unknown';
    $orderData = $payload['data'];

    // 3. 呼叫 Anser-EDA 的 Orchestrator (Saga)
    // 這裡是你將 Gateway 納入 EDA 的關鍵
    try {
        $orchestrator = new CreateOrderOrchestrator();
        
        // TODO: Anser 需要能接受並透傳 traceId (這部分是你的研究重點)
        // $orchestrator->setTraceId($traceId); 

        $result = $orchestrator->build($orderData)->start();

        if ($result->isSuccess()) {
            echo " [v] Saga Completed. TraceID: $traceId\n";
            $msg->ack(); // 確認消費成功
        } else {
            echo " [!] Saga Failed. Executing Compensation...\n";
            $msg->ack(); // 即使失敗，Saga 已處理補償，故可視為消費完成 (或視需求重試)
        }
    } catch (Exception $e) {
        echo " [Error] " . $e->getMessage() . "\n";
        $msg->nack(true); // 系統錯誤則退回佇列
    }
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('order_queue', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();