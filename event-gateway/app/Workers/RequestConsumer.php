<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use SDPMlab\AnserEDA\EventBus;
use SDPMlab\AnserEDA\MessageQueue\MessageBus;
use SDPMlab\AnserEDA\EventStore\EventStoreDB;
use App\Events\OrderCreateRequestedEvent;
use App\Handlers\OrderCreateHandler;

// RabbitMQ 連線資訊
$rabbitHost = getenv('RABBITMQ_HOST') ?: 'localhost';
$rabbitPort = (int) (getenv('RABBITMQ_PORT') ?: 5672);
$rabbitUser = getenv('RABBITMQ_USER') ?: 'guest';
$rabbitPass = getenv('RABBITMQ_PASS') ?: 'guest';

// EventStoreDB 連線資訊（若未設置，採預設帳密）
$eventStoreHost = getenv('EVENTSTORE_HOST') ?: 'localhost';
$eventStorePort = (int) (getenv('EVENTSTORE_HTTP_PORT') ?: 2113);
$eventStoreUser = getenv('EVENTSTORE_USER') ?: 'admin';
$eventStorePass = getenv('EVENTSTORE_PASS') ?: 'changeit';

// 建立連線
$connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPass);
$channel = $connection->channel();
$queueName = getenv('REQUEST_QUEUE') ?: 'request_queue';
$channel->queue_declare($queueName, false, true, false, false);

// 建立 EventBus 依賴
$messageBus = new MessageBus($channel);
$eventStoreDB = new EventStoreDB($eventStoreHost, $eventStorePort, $eventStoreUser, $eventStorePass);
$eventBus = new EventBus($messageBus, $eventStoreDB);

// --- [初始化區] 註冊事件與 Handler 的對應關係 ---
$eventBus->registerHandler(
    OrderCreateRequestedEvent::class,
    [new OrderCreateHandler(), 'handle']
);
// ---------------------------------------------

echo " [*] Worker started (Event-Driven Mode). Waiting...\n";

$callback = function ($msg) use ($eventBus) {
    echo ' [x] Received Raw Message', "\n";
    
    $payload = json_decode($msg->body, true);
    $traceId = $payload['id'] ?? 'unknown';
    $orderData = $payload['data'] ?? [];

    try {
        // 建立事件並丟入 EventBus
        $event = new OrderCreateRequestedEvent($traceId, $orderData);
        $eventBus->dispatch($event);

        echo " [v] Event Dispatched Successfully.\n";
        $msg->ack(); // 只要事件發送成功，我們就視為 Queue 任務完成

    } catch (Exception $e) {
        echo " [!] System Error: " . $e->getMessage() . "\n";
        // 如果是 Handler 拋出的錯誤，代表 Saga 連啟動都失敗，或者需要重試
        $msg->nack(true); 
    }
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume($queueName, '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
