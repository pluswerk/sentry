<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Handler;

use Pluswerk\Sentry\Traits\ExceptionHandlerTrait;

class ProductionExceptionHandler extends \TYPO3\CMS\Core\Error\ProductionExceptionHandler
{
    use ExceptionHandlerTrait;
}
