<?php

call_user_func(function () {

    if (getenv('DISABLE_SENTRY')) {
        return;
    }

    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Pluswerk\Sentry\SentryUtility::class)->getClient();
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\Pluswerk\Sentry\ErrorHandler\ProductionExceptionHandler::class]['className'] = \Pluswerk\Sentry\ErrorHandler\ProductionExceptionHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'] = \Pluswerk\Sentry\ErrorHandler\DebugExceptionHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'] = \Pluswerk\Sentry\ErrorHandler\ProductionExceptionHandler::class;
});
