<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Queue;

use JsonSerializable;
use Sentry\EventType;

class Entry implements JsonSerializable
{
    private string $dsn;

    private string $payload;

    private string $type;

    public function __construct(string $dsn, string $type, string $payload)
    {
        $this->dsn = $dsn;
        $this->type = $type;
        $this->payload = $payload;
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function isTransaction(): bool
    {
        return $this->type === (string)EventType::transaction();
    }

    /**
     * @return array{dsn: string, type: string, payload: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'dsn' => $this->dsn,
            'type' => $this->type,
            'payload' => $this->payload,
        ];
    }
}
