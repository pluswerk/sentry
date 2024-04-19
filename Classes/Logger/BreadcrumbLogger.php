<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Logger;

use Exception;
use Psr\Log\LogLevel;
use Sentry\Breadcrumb;
use Pluswerk\Sentry\Service\Sentry;
use Sentry\State\HubInterface;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class BreadcrumbLogger extends AbstractWriter implements SingletonInterface
{
    public function writeLog(LogRecord $record): WriterInterface
    {
        $hub = Sentry::getInstance()->getHub();
        if (!$hub instanceof HubInterface) {
            return $this;
        }

        if (!ExtensionManagementUtility::isLoaded('sentry')) {
            return $this;
        }

        //send breadcrumb to sentry
        $hub->addBreadcrumb(
            new Breadcrumb(
                match ($record->getLevel()) {
                    LogLevel::EMERGENCY => Breadcrumb::LEVEL_FATAL,
                    LogLevel::ALERT => Breadcrumb::LEVEL_WARNING,
                    LogLevel::CRITICAL => Breadcrumb::LEVEL_FATAL,
                    LogLevel::ERROR => Breadcrumb::LEVEL_ERROR,
                    LogLevel::WARNING => Breadcrumb::LEVEL_WARNING,
                    LogLevel::NOTICE => Breadcrumb::LEVEL_INFO,
                    LogLevel::INFO => Breadcrumb::LEVEL_INFO,
                    LogLevel::DEBUG => Breadcrumb::LEVEL_DEBUG,
                    default => throw new Exception(sprintf('Log level not supported "%s"', $record->getLevel())),
                },
                Breadcrumb::TYPE_DEFAULT,
                $record->getComponent(),
                $record->getMessage(),
                $record->getData(),
                $record->getCreated()
            )
        );
        return $this;
    }
}
