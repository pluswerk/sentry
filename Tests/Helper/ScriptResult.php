<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Tests\Helper;

final readonly class ScriptResult
{
    public function __construct(
        public string $output,
        public int $exitCode,
    )
    {
    }

    public function emptyOutput(): bool
    {
        return trim($this->output) === '';
    }

    public function ok(): bool
    {
        return $this->exitCode === 0;
    }

    public function error(): bool
    {
        return $this->exitCode !== 0;
    }
}
