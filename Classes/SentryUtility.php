<?php
declare(strict_types=1);

namespace Pluswerk\Sentry;

use Raven_ErrorHandler;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class SentryLogWriter
 * @package Pluswerk\Sentry\Writer
 */
final class SentryUtility implements SingletonInterface
{
    /**
     * @var callable
     */
    private $oldErrorHandler;

    /**
     * @var SentryClient
     */
    private $client;

    public function handleException(\Throwable $throwable)
    {
        $connection = $this->getClient();
        $connection->captureException($throwable);
    }

    public function errorHandler($type, $message, $file = '', $line = 0, array $context = [])
    {
        $shouldHandleErrors = $type & $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'];
        if ($this->oldErrorHandler && $shouldHandleErrors !== 0) {
            $handler = $this->oldErrorHandler;
            return $handler(...\func_get_args());
        }
        return false;
    }

    public function getClient(): SentryClient
    {
        if (!$this->client) {
            $this->oldErrorHandler = set_error_handler([$this, 'errorHandler'], E_ALL);
            $this->client = new SentryClient($this->getOptions());
            $this->registerErrorHandler($this->client);
        }
        return $this->client;
    }

    private function getOptions(): array
    {
        $options = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sentry'] ?? [];
        $options['environment'] = $this->getTypo3Env();
        return $options;
    }

    private function getTypo3Env(): string
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext()->__toString();
    }

    private function registerErrorHandler(SentryClient $client)
    {
        $errorHandler = new Raven_ErrorHandler($client, false, $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors']);
        $errorHandler->registerExceptionHandler();
        $errorHandler->registerErrorHandler();
        $errorHandler->registerShutdownFunction();
    }
}
