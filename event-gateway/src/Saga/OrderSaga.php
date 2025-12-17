<?php

namespace Anser\ApiGateway\Saga;

use App\Events\InventoryDeductedEvent;
use App\Events\OrderCreatedEvent;
use App\Events\OrderCreateRequestedEvent;
use App\Events\OrderCompletedEvent;
use App\Events\PaymentProcessedEvent;
use App\Events\RollbackInventoryEvent;
use App\Events\RollbackOrderEvent;
use ReflectionClass;
use SDPMlab\Anser\Service\ConcurrentAction;
use SDPMlab\AnserEDA\Attributes\EventHandler;
use SDPMlab\AnserEDA\EventBus;
use Services\Models\OrderProductDetail;
use Services\OrderService;
use Services\ProductionService;
use Services\UserService;

class OrderSaga
{
    private EventBus $eventBus;
    private UserService $userService;
    private OrderService $orderService;
    private ProductionService $productionService;
    private string $userKey = '1';
    private string $orderId;
    private array $productList = [];

    public function __construct(EventBus $eventBus)
    {
        $this->eventBus = $eventBus;
        $this->userService = new UserService();
        $this->orderService = new OrderService();
        $this->productionService = new ProductionService();
        $this->registerEventHandlers();
    }

    #[EventHandler]
    public function onOrderCreateRequested(OrderCreateRequestedEvent $event)
    {
        $this->log("Saga Step 1: 收到訂單建立請求");
        $productList = $event->orderData['productList'] ?? [];
        $this->userKey = (string)($event->orderData['userKey'] ?? $this->userKey);
        // 取得最新價格
        foreach ($productList as &$product) {
            $price = $this->productionService
                ->productInfoAction((int)$product['p_key'])
                ->do()->getMeaningData()['data']['price'] ?? null;
            if (!is_int($price)) {
                $product['price'] = $price;
            }
        }
        $this->generateProductList($productList);
        // 產生 orderId
        $orderId = $this->generateOrderId();
        // 新增訂單
        $info = $this->orderService
            ->createOrderAction($this->userKey, $orderId, $this->productList)
            ->do()->getMeaningData();
        $total = $info['total'] ?? 1000;
        $this->log("[x] 訂單建立成功");
          // 發送下一步消息
        $this->publish(OrderCreatedEvent::class, [
            'orderId' => $orderId,
            'userKey' => $this->userKey,
            'productList' => $this->productList,
            'total' => $total
        ]);   
    }

    #[EventHandler]
    public function onOrderCreated(OrderCreatedEvent $event)
    {
        $this->log("Saga Step 2: 訂單建立，開始扣庫存");

        $successfulDeductions = [];
        $inventoryFailed = false;
       
        $concurrent = new ConcurrentAction();
        $actions = [];

        foreach ($event->productList as $index => $product) {
            $actions["product_{$index}"] = $this->productionService->reduceInventory($product['p_key'], $event->orderId, $product['amount']);
        }

        $concurrent->setActions($actions)->send();
        $this->log("[x] 扣減庫存成功");
         /*
        $results = $concurrent->getActionsMeaningData();
       
        foreach ($results as $index => $result) {
            $info = $result->getMeaningData();
            if ($this->isSuccess($info)) {
                $successfulDeductions[] = $event->productList[$index];
            } else {
                $inventoryFailed = true;
                break;
            }
        }

        if ($inventoryFailed) {
            $this->compensate(RollbackInventoryEvent::class, [
                'orderId' => $event->orderId,
                'userKey' => $event->userKey,
                'successfulDeductions' => $successfulDeductions,
            ]);
            return;
        }
        */
        $this->publish(InventoryDeductedEvent::class, [
            'orderId' => $event->orderId,
            'userKey' => $event->userKey,
            'productList' => $successfulDeductions,
            'total' => $event->total
        ]);
    }

    #[EventHandler]
    public function onInventoryDeducted(InventoryDeductedEvent $event)
    {
        $this->log("Saga Step 3: 開始支付");
        $info = $this->userService
		->walletChargeAction
		($event->userKey, $event->orderId, $event->total)
		->do()->getMeaningData();
        if (!$this->isSuccess($info)) {
            $this->log("[x] 支付失敗，開始回滾");
			#發送回滾訊息
            $this->compensate(RollbackInventoryEvent::class, [
                'orderId' => $event->orderId,
                'userKey' => $event->userKey,
                'successfulDeductions' => $event->productList
            ]);
            return;
        }
        $this->log("[x] 支付成功");
		#下一步訊息
        $this->publish(PaymentProcessedEvent::class, [
            'orderId' => $event->orderId,
            'success' => true
        ]);
    }

    #[EventHandler]
    public function onPaymentProcessed(PaymentProcessedEvent $event)
    {
        if ($event->success) {
            $this->log("✅ Saga Step 4: 訂單完成！");
        }
    }

    #[EventHandler]
    public function onRollbackInventory(RollbackInventoryEvent $event)
    {
        $this->log("RollbackSaga Step 2: 回滾已扣減庫存");
		#進行回滾
        foreach ($event->successfulDeductions as $product) {
            $info = $this->productionService
			->addInventoryCompensateAction
			($product['p_key'], $event->orderId, $product['amount']
			)->do()->getMeaningData(); 
        }
		#發送下一個訊息
        $this->publish(RollbackOrderEvent::class, [
            'orderId' => $event->orderId,
            'userKey' => $event->userKey
        ]);
    }


    #[EventHandler]
    public function onRollbackOrder(RollbackOrderEvent $event)
    {
        $this->log("❌ RollbackSaga Step 1: 取消訂單");

        $info = $this->orderService
            ->compensateOrderAction($event->userKey, $event->orderId)->do()->getMeaningData();

        if ($this->isSuccess($info)) {
            $this->log("✅ 訂單取消成功");
        } else {
            $this->log("❌ 訂單取消失敗");
        }
    }

    private function generateProductList(array $data): void
    {
        $this->productList = array_map(function ($product) {
            return new OrderProductDetail(
                p_key: $product['p_key'],
                price: (int)($product['price'] ?? 0),
                amount: $product['amount']
            );
        }, $data);
    }

    public function generateOrderId(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    /**
     * 利用 EventHandler attribute 自動註冊事件處理器。
     */
    private function registerEventHandlers(): void
    {
        $reflection = new ReflectionClass($this);
        foreach ($reflection->getMethods() as $method) {
            $attributes = $method->getAttributes(EventHandler::class);
            if (empty($attributes)) {
                continue;
            }
            $params = $method->getParameters();
            if (count($params) === 0 || !$params[0]->hasType()) {
                continue;
            }
            $eventType = $params[0]->getType()->getName();
            $this->eventBus->registerHandler($eventType, [$this, $method->getName()]);
        }
    }

    private function publish(string $eventType, array $eventData): void
    {
        $this->eventBus->publish($eventType, $eventData);
    }

    private function compensate(string $eventType, array $eventData): void
    {
        // 目前補償邏輯同樣透過事件來驅動
        $this->publish($eventType, $eventData);
    }

    private function log(string $message): void
    {
        echo $message . PHP_EOL;
    }

    private function isSuccess(mixed $info): bool
    {
        if (is_bool($info)) {
            return $info;
        }
        if (is_array($info)) {
            if (array_key_exists('success', $info)) {
                return (bool)$info['success'];
            }
            if (isset($info['status'])) {
                return in_array($info['status'], ['success', 'ok', 200], true);
            }
        }
        return true;
    }
}
