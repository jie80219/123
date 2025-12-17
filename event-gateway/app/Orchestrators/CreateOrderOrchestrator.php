<?php

namespace App\Orchestrators;

use SDPMlab\Anser\Orchestration\Orchestrator;

class CreateOrderOrchestrator extends Orchestrator
{
    /** @var array<string,mixed> */
    protected array $payload = [];

    protected ?string $requestId = null;

    /**
     * Anser 的 build() 是 final，實際的編排邏輯改寫 definition()。
     *
     * @param array<string,mixed> $data
     * @param string|null $requestId
     */
    protected function definition(array $data = [], ?string $requestId = null): void
    {
        // 保留資料與追蹤 ID（這裡沒有額外 Step，純示範/占位）
        $this->payload = $data;
        $this->requestId = $requestId;
        // 若日後要新增 Saga Step，請在這裡 setStep()->addAction(...)
    }

    /**
     * 自訂成功回傳格式；目前只回傳布林與追蹤資訊。
     *
     * @return array<string,mixed>
     */
    protected function defineResult()
    {
        return [
            'success'   => $this->isSuccess(),
            'requestId' => $this->requestId,
            'data'      => $this->payload,
        ];
    }
}
