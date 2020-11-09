<?php

declare(strict_types=1);

namespace Plus\PlusSentry\Handler;

use Plus\PlusSentry\Sentry;
use TYPO3\CMS\Core\Error\ProductionExceptionHandler as Typo3ProductionExceptionHandler;

final class ProductionExceptionHandler extends Typo3ProductionExceptionHandler
{
    public function handleException(\Throwable $exception)
    {
        Sentry::withScope($exception);
        parent::handleException($exception);
    }
}
