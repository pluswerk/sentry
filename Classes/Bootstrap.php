<?php

declare(strict_types=1);

namespace Plus\PlusSentry;

use Plus\PlusSentry\Handler\DebuggingExceptionHandler;
use Plus\PlusSentry\Handler\ProductionExceptionHandler;

final class Bootstrap
{
    public static function initializeHandler(): void
    {
        $sys = &$GLOBALS['TYPO3_CONF_VARS']['SYS'];
        $sys['debugExceptionHandler'] = ProductionExceptionHandler::class;
        $sys['productionExceptionHandler'] = DebuggingExceptionHandler::class;
    }
}
