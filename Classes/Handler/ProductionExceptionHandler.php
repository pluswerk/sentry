<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Handler;

use Pluswerk\Sentry\Service\Sentry;
use Pluswerk\Sentry\Traits\ExceptionHandlerTrait;
use function dump;

class ProductionExceptionHandler extends \TYPO3\CMS\Core\Error\ProductionExceptionHandler
{
    public function __construct()
    {
        parent::__construct();
//        dump(__CLASS__);
//        Sentry::getInstance(); // TODO only use \Sentry\init(...);
    }

    use ExceptionHandlerTrait;
}
