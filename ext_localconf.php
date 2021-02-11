<?php

// note: the error handler is not here, because ext_localconf is loaded too late

defined('TYPO3_MODE') || die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
    'sentry',
    'setup',
    "@import 'EXT:sentry/Configuration/TypoScript/setup.typoscript'",
);
