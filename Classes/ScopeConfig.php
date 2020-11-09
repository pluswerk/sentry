<?php

declare(strict_types=1);

namespace Pluswerk\Sentry;

use Sentry\State\Scope;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;

final class ScopeConfig
{
    private ConfigurationManager $configurationManager;
    private ?array $config = null;

    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    public function apply(Scope $scope): void
    {
        $scope
            ->setTags($this->getTags())
            ->setUser($this->getUserContext())
            ->setExtras($this->getExtras());
    }

    public function getTags(): array
    {
        return [
            'typo3_version' => TYPO3_version,
            'typo3_mode' => TYPO3_MODE,
            'php_version' => PHP_VERSION,
            'application_context' => (string)Environment::getContext(),
        ];
    }

    public function getExtras(): array
    {
        return Environment::toArray();
    }

    private function getUserContext(): array
    {
        $user = [];
        switch (TYPO3_MODE) {
            case 'FE':
                if ($GLOBALS['TSFE']->loginUser === true) {
                    $userContext['username'] = $GLOBALS['TSFE']->fe_user->user['username'];
                    if (isset($GLOBALS['TSFE']->fe_user->user['email'])) {
                        $userContext['email'] = $GLOBALS['TSFE']->fe_user->user['email'];
                    }
                }
                break;
            case 'BE':
                if (isset($GLOBALS['BE_USER']->user['username'])) {
                    $userContext['username'] = $GLOBALS['BE_USER']->user['username'];
                    if (isset($GLOBALS['BE_USER']->user['email'])) {
                        $userContext['email'] = $GLOBALS['BE_USER']->user['email'];
                    }
                }
                break;
        }
        if (empty($user)) {
            $config = $this->getConfig();
            if (isset($config['user.'])) {
                $user = $config['user.'];
            }
        }
        return $user;
    }

    public function getConfig(): array
    {
        if ($this->config === null) {
            $config = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
            $this->config = $config['plugin.']['tx_plussentry.'] ?? [];
        }
        return $this->config;
    }
}
