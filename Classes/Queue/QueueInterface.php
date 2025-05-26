<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Queue;

interface QueueInterface
{
    public function count(): ?int;

    public function pop(): ?Entry;

    public function push(Entry $entry): void;
}
