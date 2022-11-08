<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Service;

use Pluswerk\Sentry\Transport\TransportFactory;
use Sentry\ClientBuilder;
use Sentry\ClientInterface;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Throwable;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

use function Sentry\captureException;
use function Sentry\configureScope;
use function Sentry\init;
use function Sentry\withScope;

class Sentry implements SingletonInterface
{
    protected string $dsn;
    protected bool $enabled;
    protected bool $queue;
    protected bool $withGitReleases;
    protected ScopeConfig $scopeConfig;

    /**
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     */
    public function __construct(ScopeConfig $config, ExtensionConfiguration $configuration)
    {
        $this->scopeConfig = $config;

        $this->dsn = getenv('SENTRY_DSN') ?: ($_ENV['SENTRY_DSN'] ?? null) ?: $configuration->get('sentry', 'sentry_dsn') ?: '';
        $this->queue = (bool)filter_var(getenv('SENTRY_QUEUE') ?: ($_ENV['SENTRY_QUEUE'] ?? null) ?: $configuration->get('sentry', 'sentry_queue') ?: 0, FILTER_VALIDATE_INT);
        $disabled = filter_var($env['DISABLE_SENTRY'] ?? $configuration->get('sentry', 'force_disable_sentry'), FILTER_VALIDATE_INT);

        $this->enabled = $disabled === 0 && $this->dsn;

        $git = $configuration->get('sentry', 'enable_git_hash_releases') ?? false;
        $this->withGitReleases = $git === '1';

        $this->setup();
    }

    protected function setup(): void
    {
        if ($this->enabled === false) {
            return;
        }

        $options = [
            'environment' => preg_replace('/[\/\s]/', '', Environment::getContext()),
            'dsn' => $this->dsn,
            'attach_stacktrace' => true,
        ];

        if ($this->withGitReleases) {
            $options['release'] = shell_exec('git rev-parse HEAD');
        }

        if ($this->queue) {
            $transportFactory = new TransportFactory();
            $builder = ClientBuilder::create(array_filter($options));
            $builder->setTransportFactory($transportFactory);
            SentrySdk::getCurrentHub()->bindClient($builder->getClient());
        }

        if (!$this->queue) {
            init(array_filter($options));
        }

        configureScope(
            function (Scope $scope): void {
                $this->populateScope($scope);
            }
        );
    }

    protected function populateScope(Scope $scope): void
    {
        $this->scopeConfig->apply($scope);
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public static function getInstance(): self
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        return $objectManager->get(Sentry::class);
    }

    public function getClient(): ?ClientInterface
    {
        return SentrySdk::getCurrentHub()->getClient();
    }

    public function withScope(Throwable $exception, callable $withScope = null): void
    {
        $withScope ??= fn(Scope $scope) => $this->populateScope($scope);
        withScope(
            function (Scope $scope) use ($withScope, $exception): void {
                $withScope($scope);
                captureException($exception);
            }
        );
    }
}
