<?php
declare(strict_types=1);

namespace Pluswerk\Sentry\ErrorHandler;

use Pluswerk\Sentry\SentryUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProductionExceptionHandler extends \TYPO3\CMS\Core\Error\ProductionExceptionHandler
{
    public function handleException(\Throwable $exception)
    {
        GeneralUtility::makeInstance(SentryUtility::class)->handleException($exception);
        parent::handleException($exception);
    }
}
