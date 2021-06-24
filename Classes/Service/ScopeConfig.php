<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Service;

use Sentry\State\Scope;
use TYPO3\CMS\Core\Core\Environment;

class ScopeConfig
{
    public function apply(Scope $scope): void
    {
        $scope
            ->setTags($this->getTags())
            ->setUser($this->getUserContext())
            ->setExtras($this->getExtras());
    }

    protected function getTags(): array
    {
        return [
            'typo3_version' => TYPO3_version,
            'typo3_mode' => TYPO3_MODE,
            'php_version' => PHP_VERSION,
            'application_context' => (string)Environment::getContext()
        ];
    }

    protected function getExtras(): array
    {
        return Environment::toArray();
    }

    protected function getUserContext(): array
    {
        $user = [];
        switch (TYPO3_MODE) {
            case 'FE':
                if ($GLOBALS['TSFE']->loginUser === true) {
                    $user['username'] = $GLOBALS['TSFE']->fe_user->user['username'];
                    if (isset($GLOBALS['TSFE']->fe_user->user['email'])) {
                        $user['email'] = $GLOBALS['TSFE']->fe_user->user['email'];
                    }
                }
                break;
            case 'BE':
                if (isset($GLOBALS['BE_USER']->user['username'])) {
                    $user['username'] = $GLOBALS['BE_USER']->user['username'];
                    if (isset($GLOBALS['BE_USER']->user['email'])) {
                        $user['email'] = $GLOBALS['BE_USER']->user['email'];
                    }
                }
                break;
        }
        return $user;
    }
}
