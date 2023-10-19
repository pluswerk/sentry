<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Queue;

use JsonSerializable;
use Sentry\EventType;

class Entry implements JsonSerializable
{
    public function __construct(private string $dsn, private bool $isEnvelope, private string $payload)
    {
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function isEnvelope(): bool
    {
        return $this->isEnvelope;
    }

    /**
     * @return array{dsn: string, isEnvelope: bool, payload: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'dsn' => $this->dsn,
            'isEnvelope' => $this->isEnvelope,
            'payload' => $this->payload,
        ];
    }
}
