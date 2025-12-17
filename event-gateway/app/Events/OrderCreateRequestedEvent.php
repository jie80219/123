<?php

namespace App\Events;

/**
 * 代表「建立訂單」的意圖事件，攜帶追蹤 ID 與訂單資料。
 */
class OrderCreateRequestedEvent
{
    public string $traceId;

    /** @var array<string,mixed> */
    public array $orderData;

    public function __construct(string $traceId, array $orderData = [])
    {
        $this->traceId = $traceId;
        $this->orderData = $orderData;
    }
}
