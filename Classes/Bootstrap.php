<?php

declare(strict_types=1);

namespace Pluswerk\Sentry;

use Pluswerk\Sentry\Handler\DebugExceptionHandler;
use Pluswerk\Sentry\Handler\ProductionExceptionHandler;

class Bootstrap
{
    public function initializeHandler(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'] = DebugExceptionHandler::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'] = ProductionExceptionHandler::class;
    }
}
