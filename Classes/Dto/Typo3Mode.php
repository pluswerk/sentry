<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Dto;

enum Typo3Mode
{
    case unknown;
    case frontend;
    case backend;
    case cli;

    public function isFrontend(): bool
    {
        return $this === self::frontend;
    }

    public function isBackend(): bool
    {
        return $this === self::backend;
    }

    public function isCli(): bool
    {
        return $this === self::cli;
    }

    public function isUnknown(): bool
    {
        return $this === self::unknown;
    }

    public function isHttp(): bool
    {
        return match ($this) {
            self::frontend, self::backend => true,
            self::cli, self::unknown => false,
        };
    }
}
