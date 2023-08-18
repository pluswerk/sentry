<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Service;

use Psr\Http\Message\ServerRequestInterface;
use Sentry\State\Scope;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
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
            'typo3_mode' => $this->getApplicationTypeString(),
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
     * @return array{username: string, id?: string, email?: string}|array{}
     */
    protected function getUserContext(): array
    {
        $username = null;
        $userAuthentication = null;

        $applicationType = $this->getApplicationTypeString();

        if ($applicationType === 'frontend') {
            $username = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'username');
            $userAuthentication = ($GLOBALS['TSFE'] ?? null)?->fe_user;
        }

        if ($applicationType !== 'cli' && !$username) {
            $username = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('backend.user', 'username');
            $userAuthentication = $GLOBALS['BE_USER'] ?? null;
        }

        $user = [];
        if ($username) {
            $user['username'] = $username;
            if ($userAuthentication instanceof AbstractUserAuthentication && is_array($userAuthentication->user)) {
                $user['id'] = $userAuthentication->user_table . ':' . ($userAuthentication->user['uid'] ?? null);
                $user['email'] = $userAuthentication->user['email'] ?? null;
            }
        }

        return array_filter($user);
    }

    protected function getApplicationType(): ?ApplicationType
    {
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            return ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST']);
        }

        return null;
    }

    private function getApplicationTypeString(): string
    {
        $applicationType = $this->getApplicationType();
        if ($applicationType?->isFrontend()) {
            return 'frontend';
        }

        if ($applicationType?->isBackend()) {
            return 'backend';
        }

        return 'cli';
    }
}
