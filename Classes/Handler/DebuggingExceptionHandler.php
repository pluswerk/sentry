<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Handler;

use Pluswerk\Sentry\Sentry;
use TYPO3\CMS\Core\Error\DebugExceptionHandler;

final class DebuggingExceptionHandler extends DebugExceptionHandler
{
    public function handleException(\Throwable $exception)
    {
        Sentry::withScope($exception);
        parent::handleException($exception);
    }
}
