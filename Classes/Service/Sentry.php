<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Service;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use Pluswerk\Sentry\Transport\TransportFactory;
use Sentry\ClientBuilder;
use Sentry\ClientInterface;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Throwable;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function Sentry\captureException;
use function Sentry\configureScope;
use function Sentry\init;
use function Sentry\withScope;

class Sentry implements SingletonInterface
{
    public function __construct(
        protected ScopeConfig $scopeConfig,
        protected ConfigService $config,
    ) {
        $this->setup();
    }

    protected function setup(): void
    {
        if ($this->config->isDisabled()) {
            return;
        }

        $options = [
            'environment' => preg_replace('#[\/\s]#', '', (string)Environment::getContext()),
            'dsn' => $this->config->getDsn(),
            'attach_stacktrace' => true,
            'error_types' => $this->config->getErrorsToReport(),
        ];

        if ($this->config->isWithGitReleases()) {
            $options['release'] = shell_exec('git rev-parse HEAD');
        }

        if ($this->config->isQueueEnabled()) {
            $builder = ClientBuilder::create(array_filter($options));
            $builder->setTransportFactory(new TransportFactory());
            SentrySdk::getCurrentHub()->bindClient($builder->getClient());
        } else {
            init(array_filter($options));
        }

        configureScope(fn(Scope $scope) => $this->scopeConfig->apply($scope));
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function getInstance(): self
    {
        return GeneralUtility::makeInstance(Sentry::class);
    }

    public function getClient(): ?ClientInterface
    {
        return SentrySdk::getCurrentHub()->getClient();
    }

    public function withScope(Throwable $exception, callable $withScope = null): void
    {
        $withScope ??= static fn(Scope $scope) => null;
        withScope(
            static function (Scope $scope) use ($withScope, $exception): void {
                $withScope($scope);
                captureException($exception);
            }
        );
    }
}
