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

# Testing

## TODOS:

- [ ] Automated tests + run-test-server
- [ ] Update to the newest sentry SDK version
- [ ] only catch exceptions once


Idea:
- use `\Sentry\init()` as early as possible (HTTP and CLI) And use it always, not only if we handle a LogEntry or Exception.
- after that you should be able to use `\Sentry\captureException()` and `\Sentry\captureMessage()` in your code. Without the Singleton.
- Check if the normal error handler of TYPO3 still works.
- Maybe we do not need to overwrite the TYPO3 ErrorHandlers at all? if we run `\Sentry\init()` at the correct time.

## how to run the tests:

```bash
composer install
composer run-test-server
# different terminal:
composer test
````

## Testing setup

Options:
- local sentry?
- sentry test server (public)?
- php script that mocks sentry api?
- mocking sentry SDK?
  - Pro: No need for real servers
  - Con: can not test Queue?

Test setup?
- Unit test?
- Functional test? 
- Integration test !!
  - We want to test the real integration of EXT:sentry into TYPO3.
  - Real Webserver, real TYPO3, mocked Sentry
  - Real Request
  - Real Response
  - Real Cli Command
- mocked api writes to file?
- test runner reads from file?
- Test runner needs to be able to
  - run CLI commands
  - run Backend requests
  - run Frontend requests
  - read files?
- => best case Test runner: PHPUnit + Guzzle + shell_exec



## Test cases:

- Test with all compatible TYPO3 versions
- Test with all compatible PHP versions
- Test with and without Queue

- Test with frontend User
- Test with backend User
- Test with no user
- 
- Test in CLI
- Test in Backend
- Test in Frontend
- Test in first middleware
- Test in last middleware
- Test in Extbase Action
- Test in ContentElement (ViewHelper) (ContentObjectProductionExceptionHandler)
- Test with DebugExceptionHandler
- Test with ProductionExceptionHandler
- Test Sentry::getInstance()->getClient()?->captureException(new Exception('Test Exception'));
- Test throw new Exception('Test Exception');
- Test Queue
