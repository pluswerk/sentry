[![GitHub License](https://img.shields.io/github/license/pluswerk/sentry.svg?style=flat-square)](https://github.com/pluswerk/sentry/blob/master/LICENSE.txt)

# Pluswerk TYPO3 Sentry PHP Client

## Features:

- has LogWriter that enables you to write any log entries into sentry
- Logs all exceptions caught by the TYPO3 error handling.
- Logs all exceptions that are caught by the TYPO3 ProductionExceptionHandler ContentObject.
  - This handles the `Oops, an error occurred! code: 2023080912232477707e9b` errors in your site.
  - It displays the error message as normal + a link with only a &nbsp; after the code. So a normal user will not see the link.
  - The link directly opens the sentry with the correct error.

### Quickstart:

- Add environment variables:
  - `SENTRY_DSN=https://dsn-to-your@sentry.io/instance`
  - `SENTRY_ORGANISATION=sentry` (optional) if the organisation of your sentry is different. is used in eg. the Oops, an error occurred! Code: 2023080912232477707e9b
  - `DISABLE_SENTRY` (optional) Disable Sentry by setting this to 1
  - `SENTRY_QUEUE` (optional) Enable queue system by setting this to 1
  - `SENTRY_ERRORS_TO_REPORT` (optional) The Errors to Report as number, e.g. 4096 for E_REVOERABLE_ERROR
- Add the following line to your `AdditionalConfiguration.php`
  - `(new \Pluswerk\Sentry\Bootstrap())->initializeHandler();`
- If you enabled SENTRY_QUEUE
  - Add `typo3 pluswerk:sentry:flush` to your scheduling service
  - Add environment before the command if you want to report errors while running the command `SENTRY_QUEUE=0 typo3 pluswerk:sentry:flush`

### Settings

The Extension comes with a couple of settings in the TYPO3-Backend:
- `force_disable_sentry` -> Forcefully override the ENV by disabling Sentry in backend
- `enable_git_hash_releases` (default yes) -> Automatically track releases with the current git hash (only works if git is installed)

#### Configuring the scope

Sometimes it might be necessary to additionally configure the scope of a sentry event.  
For this, the method `\Pluswerk\Sentry\Sentry::withScope($exception, $scopeCallback)` comes in play.  
The arguments are similar to `https://docs.sentry.io/platforms/php/enriching-events/scopes/#local-scopes`.
The first argument requires the exception to be thrown and the second is a callback, 
for where you can apply custom settings to the Sentry Scope.  
There is no need to additionally write `captureException` within that callback.

Example:
```php
Sentry::getInstance()->withScope($exception, fn(Scope $scope) => $scope->setTag('oops_code', $oopsCode));
```


### SentryLogger

You can write this in your additional.php if you want all warnings from the TYPO3 log to be logged in Sentry:

```php
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][LogLevel::WARNING] = [
        SyslogWriter::class => [],
        SentryLogger::class => [],
    ];
```
