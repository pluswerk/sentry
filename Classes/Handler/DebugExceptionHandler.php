<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Handler;

use Pluswerk\Sentry\Service\Sentry;
use Pluswerk\Sentry\Traits\ExceptionHandlerTrait;

class DebugExceptionHandler extends \TYPO3\CMS\Core\Error\DebugExceptionHandler
{
    public function __construct()
    {
        parent::__construct();
//        dump(__CLASS__);
//        Sentry::getInstance(); // TODO only use \Sentry\init(...);
    }

    use ExceptionHandlerTrait;
}
