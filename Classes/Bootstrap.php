<?php

declare(strict_types=1);

namespace Pluswerk\Sentry;

use Pluswerk\Sentry\Handler\DebuggingExceptionHandler;
use Pluswerk\Sentry\Handler\ProductionExceptionHandler;

final class Bootstrap
{
    public static function initializeHandler(): void
    {
        $sys = &$GLOBALS['TYPO3_CONF_VARS']['SYS'];
        $sys['debugExceptionHandler'] = DebuggingExceptionHandler::class;
        $sys['productionExceptionHandler'] = ProductionExceptionHandler::class;
    }
}
