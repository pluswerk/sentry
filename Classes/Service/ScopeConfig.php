<?php

declare(strict_types=1);

namespace Pluswerk\Sentry\Service;

use Pluswerk\Sentry\Dto\Typo3Mode;
use Psr\Http\Message\ServerRequestInterface;
use Sentry\State\Scope;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Information\Typo3Version;

use function is_int;
use function is_string;

class ScopeConfig
{
    public function __construct(private Context $context)
    {
    }

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
            'typo3_mode' => $this->getApplicationTypeString()->name,
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
     * @return array{username?: non-falsy-string, id?: non-falsy-string, email?: non-falsy-string}|array{}
     */
    protected function getUserContext(): array
    {
        $username = null;
        $userId = null;
        $table = null;
        $userAuthentication = null;

        $applicationType = $this->getApplicationTypeString();

        if ($applicationType->isFrontend()) {
            $username = $this->context->getPropertyFromAspect('frontend.user', 'username');
            $userId = $this->context->getPropertyFromAspect('frontend.user', 'id');
            $table = 'fe_users';
            $userAuthentication = ($GLOBALS['TYPO3_REQUEST'] ?? null)?->getAttribute('frontend.user'); // TYPO3 13+
            $userAuthentication ??= ($GLOBALS['TSFE'] ?? null)?->fe_user; // TYPO3 < 13
        }

        if ($applicationType->isHttp() && !$username) {
            $username = $this->context->getPropertyFromAspect('backend.user', 'username');
            $userId = $this->context->getPropertyFromAspect('backend.user', 'id');
            $table = 'be_users';
            $userAuthentication = $GLOBALS['BE_USER'] ?? null;
        }

        $user = [];

        if (is_string($username) && $username) {
            $user['username'] = $username;
        }

        if (is_string($table) && is_int($userId)) {
            $user['id'] = $table . ':' . $userId;
        }

        $email = $userAuthentication?->user['email'] ?? null;
        if (is_string($email) && $email) {
            $user['email'] = $email;
        }

        return $user;
    }

    private function getApplicationType(): ?ApplicationType
    {
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            return ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST']);
        }

        return null;
    }

    private function getApplicationTypeString(): Typo3Mode
    {
        $applicationType = $this->getApplicationType();
//        echo '<pre>' . new Exception()->getTraceAsString() . '</pre>';
//        dd($applicationType, $GLOBALS['TYPO3_REQUEST']);
        if ($applicationType?->isFrontend()) {
            return Typo3Mode::frontend;
        }

        if ($applicationType?->isBackend()) {
            return Typo3Mode::backend;
        }


        return Environment::isCli() ? Typo3Mode::cli : Typo3Mode::unknown;
    }
}
