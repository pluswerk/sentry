<?php
declare(strict_types=1);

namespace Pluswerk\Sentry;

use Raven_Client;

/**
 * Class SentryLogWriter
 * @package Pluswerk\Sentry\Writer
 */
final class SentryClient extends Raven_Client
{
    public function capture($data = null, $logger = null, $vars = null)
    {
        $this->tags_context([
            'typo3_version' => TYPO3_version,
            'typo3_mode' => TYPO3_MODE,
            'php_version' => PHP_VERSION,
            'application_context' => \TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext()->__toString(),
        ]);
        $this->user_context($this->getUserContext());
        return parent::capture($data, $logger, $vars);
    }

    /**
     * @return array
     */
    protected function getUserContext(): array
    {
        $userContext = [];
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
        }
        return $userContext;
    }
}
