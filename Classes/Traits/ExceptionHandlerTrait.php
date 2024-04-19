<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Traits;

use Pluswerk\Sentry\Service\Sentry;
use Throwable;

trait ExceptionHandlerTrait
{
    public function handleException(Throwable $exception): void
    {
        try {
            Sentry::getInstance()->withScope($exception);
        } catch (Throwable) {
            //ignore $sentryError
        }

        /** @noinspection PhpMultipleClassDeclarationsInspection */
        parent::handleException($exception);
    }
}
