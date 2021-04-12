<?php

declare(strict_types=1);

namespace Pluswerk\Sentry;

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

    public function __construct(ScopeConfig $config, ExtensionConfiguration $configuration, string $dsn = '', bool $disabled = false)
    {
        $this->scopeConfig = $config;
        $this->dsn = $dsn;
        $this->enabled = !$disabled;
        $this->setupExtensionConfiguration($configuration);
        $this->setup();
    }

    public function getClient(): ?ClientInterface
    {
        return SentrySdk::getCurrentHub()->getClient();
    }

    public function setupExtensionConfiguration(ExtensionConfiguration $configuration): void
    {
        $disabled = $configuration->get('sentry', 'force_disable_sentry');
        $dsn = $configuration->get('sentry', 'sentry_dsn');
        if ($dsn) {
            $this->dsn = $dsn;
        }
        if ($disabled || !$this->dsn) {
            $this->enabled = false;
        }
        $git = $configuration->get('sentry', 'enable_git_hash_releases') ?? false;
        $this->withGitReleases = $git === '1';
    }

    public function setup(): void
    {
        if ($this->enabled === false) {
            return;
        }
        $options = $this->scopeConfig->getConfig()['options.'] ?? [];
        $options['environment'] = preg_replace('/[\/\s]/', '', Environment::getContext());
        $options['dsn'] = $this->dsn;
        $options['attach_stacktrace'] = true;

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
