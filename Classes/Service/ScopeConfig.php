<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Service;

use Sentry\State\Scope;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
            'typo3_version' => (new Typo3Version())->getVersion(),
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
                $frontendUserAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
                if (!$frontendUserAspect instanceof UserAspect) {
                    return [];
                }
                if ($frontendUserAspect->isLoggedIn()) {
                    $user['username'] = $frontendUserAspect->get('username');
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
