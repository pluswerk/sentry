<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Service;

use InvalidArgumentException;
use Pluswerk\Sentry\Transport\MockTransportFactory;
use Pluswerk\Sentry\Transport\QueueTransportFactory;
use Sentry\ClientBuilder;
use Sentry\ClientInterface;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Throwable;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function dd;
use function getenv;
use function Sentry\captureException;
use function Sentry\configureScope;
use function Sentry\withScope;

class Sentry implements SingletonInterface
{
    public function __construct(
        protected ScopeConfig $scopeConfig,
        protected ConfigService $config,
    ) {
        dd();
        $this->setup();
    }

    public function isDisabled(): bool
    {
        return $this->config->isDisabled();
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
            'prefixes' => [Environment::getProjectPath()],
        ];

        if ($this->config->isWithGitReleases()) {
            $options['release'] = shell_exec('git rev-parse HEAD');
        }

        $builder = ClientBuilder::create(array_filter($options));
        if ($this->config->isQueueEnabled()) {
            $builder->setTransportFactory(new QueueTransportFactory());
        }

        $this->addMockIfNeeded($builder);

        SentrySdk::getCurrentHub()->bindClient($builder->getClient());

        configureScope(fn(Scope $scope) => $this->scopeConfig->apply($scope));
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getInstance(): self
    {
        if (GeneralUtility::getContainer()->has(Sentry::class)) {
            return GeneralUtility::makeInstance(Sentry::class);
        }

        return GeneralUtility::makeInstance(
            Sentry::class,
            GeneralUtility::makeInstance(ScopeConfig::class, GeneralUtility::makeInstance(Context::class)),
            GeneralUtility::makeInstance(
                ConfigService::class,
                GeneralUtility::makeInstance(ExtensionConfiguration::class),
            ),
        );
    }

    public function getClient(): ?ClientInterface
    {
        return SentrySdk::getCurrentHub()->getClient();
    }

    public function getHub(): ?HubInterface
    {
        return SentrySdk::getCurrentHub();
    }

    public function withScope(Throwable $exception, ?callable $withScope = null): void
    {
        $withScope ??= static fn(Scope $scope) => null;
        withScope(
            static function (Scope $scope) use ($withScope, $exception): void {
                $withScope($scope);
                captureException($exception);
            },
        );
    }

    private function addMockIfNeeded(ClientBuilder $builder): void
    {
        if (!getenv('SENTRY_MOCK')) {
            return;
        }

        // given from phpunit test via environment variable or header @see \Pluswerk\Sentry\Tests\Helper\MockApi
        $mockSeed = getenv('SENTRY_MOCK_SEED') ?: ($_SERVER['HTTP_X_SENTRY_MOCK_SEED'] ?? null) ?? 'ddbebe8827a39c1f3976022a7007e696'; // TODO remove default
        if (!$mockSeed) {
            return;
        }
        $builder->setTransportFactory(new MockTransportFactory($mockSeed));
    }
}
