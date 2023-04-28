<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Service;

use Psr\Http\Message\ServerRequestInterface;
use Sentry\State\Scope;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
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

    /**
     * @return array{typo3_version: string, typo3_mode: string, php_version: string, application_context: string}
     */
    protected function getTags(): array
    {
        return [
            'typo3_version' => (new Typo3Version())->getVersion(),
            'typo3_mode' => $this->getApplicationType()?->value ?? 'cli',
            'php_version' => PHP_VERSION,
            'application_context' => (string)Environment::getContext(),
        ];
    }

    /**
     * @return array<string, string|bool>
     */
    protected function getExtras(): array
    {
        return Environment::toArray();
    }

    /**
     * @return array{username: string, email: string}|array{}
     */
    protected function getUserContext(): array
    {
        $userAspect = null;
        $userAuthentication = null;

        $user = [];
        switch ($this->getApplicationType()?->value) {
            case ApplicationType::FRONTEND:
                $userAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
                $userAuthentication = $GLOBALS['TSFE']->fe_user;
                break;
            case ApplicationType::BACKEND:
                $userAspect = GeneralUtility::makeInstance(Context::class)->getAspect('backend.user');
                $userAuthentication = $GLOBALS['BE_USER'];
                break;
        }

        if ($userAspect?->isLoggedIn()) {
            $user['username'] = $userAspect->get('username');
            if ($userAuthentication instanceof AbstractUserAuthentication && isset($userAuthentication->user['email'])) {
                $user['email'] = $userAuthentication->user['email'];
            }
        }

        return $user;
    }

    protected function getApplicationType(): ?ApplicationType
    {
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            return ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST']);
        }

        return null;
    }
}
