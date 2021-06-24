[![GitHub License](https://img.shields.io/github/license/pluswerk/sentry.svg?style=flat-square)](https://github.com/pluswerk/sentry/blob/master/LICENSE.txt)

# Pluswerk TYPO3 Sentry PHP Client

### Quickstart:

- Add two environment variables:
  - `SENTRY_DSN=https://dsn-to-your@sentry.io/instance`
  - `DISABLE_SENTRY` (optional) Disable Sentry by setting this to 1
- Add the following line to your `AdditionalConfiguration.php`
  - `(new \Pluswerk\Sentry\Bootstrap())->initializeHandler();`

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
