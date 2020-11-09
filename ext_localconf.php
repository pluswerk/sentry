<?php

// note: the error handler is not here, because ext_localconf is loaded too late

defined('TYPO3_MODE') || die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
    'plus_sentry',
    'setup',
    "@import EXT:plus_sentry/Configuration/TypoScript/setup.typoscript'",
);
