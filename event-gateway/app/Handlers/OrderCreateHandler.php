<?php

namespace App\Handlers;

use App\Events\OrderCreateRequestedEvent;
use App\Orchestrators\CreateOrderOrchestrator;
use Exception;

class OrderCreateHandler
{
    /**
     * 當 EventBus 收到 'order.create.requested' 時，會執行這裡
     */
    public function handle(OrderCreateRequestedEvent $event)
    {
        echo " [Handler] Handling event for TraceID: {$event->traceId}\n";

        try {
            // 初始化你的 Saga Orchestrator
            $orchestrator = new CreateOrderOrchestrator();

            // 這裡保留你原本的調用邏輯
            // 注意：通常建議用 setOrchestratorKey 設定 traceId，但依照你的 build 方法傳入也可
            $result = $orchestrator->build($event->orderData, $event->traceId);

            // 判斷結果 (依照你的邏輯)
            $isSuccess = is_array($result) ? ($result['success'] ?? false) : (bool) $result;

            if ($isSuccess) {
                echo " [V] Saga Completed Successfully. ID: {$event->traceId}\n";
            } else {
                echo " [X] Saga Failed. Compensation started.\n";
            }

        } catch (Exception $e) {
            echo " [Error] Handler Exception: " . $e->getMessage() . "\n";
            // 在 EDA 中，這裡發生錯誤通常會拋出，讓 Worker 決定是否重試 (NACK)
            throw $e; 
        }
    }
}