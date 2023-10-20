<?php

use Pluswerk\Sentry\Logger\BreadcrumbLogger;
use Psr\Log\LogLevel;

$logLevel = ($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['sentry']['breadcrumb_log_level'] ?? '') ?: LogLevel::DEBUG;
$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][$logLevel][BreadcrumbLogger::class] = [];
