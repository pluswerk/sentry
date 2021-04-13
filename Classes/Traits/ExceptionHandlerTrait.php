<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Traits;

use Pluswerk\Sentry\Service\Sentry;
use Throwable;

trait ExceptionHandlerTrait
{
    public function handleException(Throwable $exception): void
    {
        Sentry::getInstance()->withScope($exception);
        /** @noinspection PhpMultipleClassDeclarationsInspection */
        parent::handleException($exception);
    }
}
