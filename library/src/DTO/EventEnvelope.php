<?php

namespace Anser\Shared\DTO;

use DateTimeImmutable;

class EventEnvelope
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $type, // e.g., 'order.created'
        public readonly int $version,
        public readonly DateTimeImmutable $occurredAt,
        public readonly string $correlationId, // Links request to Saga
        public readonly ?string $causationId, // Links event to previous event
        public readonly array $payload,
        public readonly array $meta = []
    ) {}

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        return new self(
            $data['eventId'],
            $data['type'],
            $data['version'],
            new DateTimeImmutable($data['occurredAt']),
            $data['correlationId'],
            $data['causationId'] ?? null,
            $data['payload'],
            $data['meta'] ?? []
        );
    }

    public function toJson(): string
    {
        return json_encode([
            'eventId' => $this->eventId,
            'type' => $this->type,
            'version' => $this->version,
            'occurredAt' => $this->occurredAt->format(DateTimeImmutable::ATOM),
            'correlationId' => $this->correlationId,
            'causationId' => $this->causationId,
            'payload' => $this->payload,
            'meta' => $this->meta,
        ]);
    }
}