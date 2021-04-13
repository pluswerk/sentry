<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Logger;

use Pluswerk\Sentry\Service\Sentry;
use Sentry\Severity;
use Sentry\State\Scope;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use function Sentry\withScope;

class SentryLogger extends AbstractWriter implements SingletonInterface
{

    public function writeLog(LogRecord $record): WriterInterface
    {
        $sentry = Sentry::getInstance();
        if (null === $sentry) {
            return $this;
        }
        $client = $sentry->getClient();

        if (
            null !== $client &&
            $record->getComponent() !== 'TYPO3.CMS.Frontend.ContentObject.Exception.ProductionExceptionHandler' &&
            ExtensionManagementUtility::isLoaded('sentry')
        ) {
            withScope(
                function (Scope $scope) use ($record, $client): void {
                    $scope->setExtra('component', $record->getComponent());
                    if ($record->getData()) {
                        $scope->setExtra('data', $record->getData());
                    }
                    $scope->setTag('source', 'logwriter');

                    switch ($record->getLevel()) {
                        case LogLevel::DEBUG:
                            $severity = Severity::debug();
                            break;
                        case LogLevel::WARNING:
                            $severity = Severity::warning();
                            break;
                        case LogLevel::ALERT:
                        case LogLevel::ERROR:
                            $severity = Severity::error();
                            break;
                        case LogLevel::NOTICE:
                        case LogLevel::INFO:
                            $severity = Severity::info();
                            break;
                        default:
                            $severity = Severity::fatal();
                    }

                    $client->captureMessage($record->getMessage(), $severity, $scope);
                }
            );
        }
        return $this;
    }
}
