<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Service;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

final class ConfigService
{
    public function __construct(private ExtensionConfiguration $configuration)
    {
    }

    private function getEnv(string $env): ?string
    {
        $var = getenv($env);
        if ($var) {
            return $var;
        }

        $var = $_ENV[$env] ?? null;
        if ($var) {
            return $var;
        }

        return null;
    }

    private function getConfig(string $path): ?string
    {
        try {
            return $this->configuration->get('sentry', $path) ?: null;
        } catch (ExtensionConfigurationPathDoesNotExistException) {
            return null;
        }
    }

    public function getDsn(): string
    {
        return $this->getEnv('SENTRY_DSN') ?? $this->getConfig('sentry_dsn') ?? '';
    }

    public function isQueueEnabled(): bool
    {
        $var = $this->getEnv('SENTRY_QUEUE') ?? $this->getConfig('sentry_queue') ?? 0;
        return (bool)filter_var($var, FILTER_VALIDATE_INT);
    }

    public function isDisabled(): bool
    {
        $var = $this->getEnv('DISABLE_SENTRY') ?? $this->getConfig('force_disable_sentry') ?? 0;
        $isDisabled = (bool)filter_var($var, FILTER_VALIDATE_INT);
        return $isDisabled || !$this->getDsn();
    }

    public function isEnabled(): bool
    {
        return !$this->isDisabled();
    }

    public function getErrorsToReport(): ?int
    {
        return (int)(
            $this->getEnv('SENTRY_ERRORS_TO_REPORT')
            ?? $this->getConfig('sentry_errors_to_report')
            ?? $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors']
            ?? (E_ALL ^ E_DEPRECATED ^ E_NOTICE ^ E_WARNING ^ E_USER_DEPRECATED)
        );
    }

    public function isWithGitReleases(): bool
    {
        $var = $this->getConfig('enable_git_hash_releases');
        return (bool)filter_var($var, FILTER_VALIDATE_INT);
    }

    public function getOrganizationName(): string
    {
        return $this->getEnv('SENTRY_ORGANISATION') ?? $this->getConfig('sentry_organisation') ?? 'sentry';
    }
}
