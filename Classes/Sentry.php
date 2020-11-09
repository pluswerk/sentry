<?php

declare(strict_types=1);

namespace Plus\PlusSentry;

use Sentry\ClientInterface;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function Sentry\captureException;
use function Sentry\configureScope;
use function Sentry\init;
use function Sentry\withScope;

final class Sentry
{
    private string $dsn;
    private bool $enabled;
    private bool $withGitReleases;
    private ScopeConfig $scopeConfig;

    public function __construct(ScopeConfig $config, ExtensionConfiguration $configuration, string $dsn, bool $enabled = true)
    {
        $this->scopeConfig = $config;
        $this->dsn = $dsn;
        $this->enabled = $enabled;
        $this->setupExtensionConfiguration($configuration);
        $this->setup();
    }

    public function getClient(): ?ClientInterface
    {
        return SentrySdk::getCurrentHub()->getClient();
    }

    public function setupExtensionConfiguration(ExtensionConfiguration $configuration): void
    {
        $disabled = $configuration->get('plus_sentry', 'force_disable_sentry');
        if ($disabled === '1') {
            $this->enabled = false;
        }
        $git = $configuration->get('plus_sentry', 'enable_git_hash_releases') ?? false;
        $this->withGitReleases = $git === '1';
    }

    public function setup(): void
    {
        if ($this->enabled === false) {
            return;
        }
        $options = $this->scopeConfig->getConfig()['options.'] ?? [];
        $options['environment'] = (string)Environment::getContext();
        $options['dsn'] = $this->dsn;
        if ($this->withGitReleases) {
            $options['release'] = shell_exec('git rev-parse HEAD');
        }
        init(array_filter($options));
        configureScope(function (Scope $scope): void {
            $this->populateScope($scope);
        });
    }

    public function populateScope(Scope $scope): void
    {
        $this->scopeConfig->apply($scope);
    }

    public static function withScope(\Throwable $exception, callable $withScope = null): void
    {
        $self = GeneralUtility::getContainer()->get(self::class);
        $withScope ??= fn (Scope $scope) => $self->populateScope($scope);
        withScope(function (Scope $scope) use ($withScope, $exception, $self): void {
            $withScope($scope);
            captureException($exception);
        });
    }
}