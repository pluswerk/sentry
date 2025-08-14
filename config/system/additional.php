<?php


use TYPO3\CMS\Core\Core\Environment;

$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['path'] = dirname(__DIR__, 2) . '/var/sqlite/cms.sqlite';

if (!is_dir(dirname($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['path']))) {
    // Create the directory if it does not exist
    mkdir(dirname($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['path']), recursive: true);
}

if(!file_exists($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['path'])) {
    touch($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['path']);
}

(new \Pluswerk\Sentry\Bootstrap())->initializeHandler();

$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'throw';

//if (!function_exists('get_error_handler')) {
//    function noop_error_handler() {
//    }
//    function get_error_handler(): ?callable {
//        $handler = set_error_handler('noop_error_handler');
//        restore_error_handler();
//        return $handler;
//    }
//}
//if (!function_exists('get_exception_handler')) {
//    function noop_exception_handler() {
//    }
//    function get_exception_handler(): ?callable {
//        $handler = set_exception_handler('noop_exception_handler');
//        restore_exception_handler();
//        return $handler;
//    }
//}

function dd(...$args): never
{
    dump(...$args);

    $isCli = Environment::isCli();
    $trace = new Exception()->getTrace();
    $callerLocation = $trace[0]['file'] ?? 'unknown file';
    $rootPath = \TYPO3\CMS\Core\Core\Environment::getProjectPath() . '/';
    $callerLocation = str_replace($rootPath, '', $callerLocation);
    $callerLine = $trace[0]['line'] ?? 'unknown line';
    $echo = 'dd called from <span style="font-family: monospace">' . $callerLocation . ':' . $callerLine . '</span>' . PHP_EOL;
    if ($isCli) {
        $colorRed = "\033[31m";
        $colorReset = "\033[0m";
        $echo = 'dd called from ' . $colorRed . $callerLocation . ':' . $callerLine . $colorReset . PHP_EOL;
    }
    die($echo);
}

function dump(...$args): void
{
    $isCli = Environment::isCli();
    foreach ($args as $arg) {
        try {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($arg, plainText: $isCli);
        } catch (Exception $e) {
            var_dump($arg);
        }
    }
    if(!$isCli) {
        $traceAsString = (new Exception())->getTraceAsString();
        $traceAsString = str_replace(\TYPO3\CMS\Core\Core\Environment::getProjectPath() . '/', '', $traceAsString);
        echo '<details><summary>Trace</summary><pre style="background: #0d0303;color: #ddd; padding:10px;">' . $traceAsString . '</pre></details>' . PHP_EOL;
    }
}
