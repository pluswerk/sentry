<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Logger;

use Sentry\ClientInterface;
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
        $client = Sentry::getInstance()->getClient();
        if (!$client instanceof ClientInterface) {
            return $this;
        }

        if ($record->getComponent() === 'TYPO3.CMS.Frontend.ContentObject.Exception.ProductionExceptionHandler') {
            return $this;
        }

        if (!ExtensionManagementUtility::isLoaded('sentry')) {
            return $this;
        }

        withScope(
            static function (Scope $scope) use ($record, $client): void {
                $scope->setExtra('component', $record->getComponent());
                if ($record->getData()) {
                    $scope->setExtra('data', $record->getData());
                }

                $scope->setTag('source', 'logwriter');
                $severity = match ($record->getLevel()) {
                    LogLevel::DEBUG => Severity::debug(),
                    LogLevel::WARNING => Severity::warning(),
                    LogLevel::ALERT, LogLevel::ERROR => Severity::error(),
                    LogLevel::NOTICE, LogLevel::INFO => Severity::info(),
                    default => Severity::fatal(),
                };
                $client->captureMessage($record->getMessage(), $severity, $scope);
            }
        );
        return $this;
    }
}
