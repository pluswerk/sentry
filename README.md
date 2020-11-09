[![GitHub License](https://img.shields.io/github/license/pluswerk/sentry.svg?style=flat-square)](https://github.com/pluswerk/sentry/blob/master/LICENSE.txt)

# Pluswerk TYPO3 Sentry PHP Client

### Getting started:

- Add two environment variables:
  - `SENTRY_DSN=https://dsn-to-your@sentry.io/instance`
  - `SENTRY_ENABLED` (optional) Disable Sentry by setting this to 0
- Add the following line to your `AdditionalConfiguration.php`
  - `\Plus\PlusSentry\Bootstrap::initializeHandler();`

That's basically it. This will already catch all Exceptions.

### Settings

The Extension comes with a couple of settings in the TYPO3-Backend:
- `force_disable_sentry` -> Forcefully override the ENV by disabling Sentry in backend
- `enable_git_hash_releases` (default yes) -> Automatically track releases with the current git hash (only works if git is installed)

### Configuration

The entire configuration for sentry is exposed via typoscript

```typo3_typoscript

plugin.tx_plussentry {
  user {
    # Add a fallback user for when no BE or FE user is set
    username = 'unknown-user'
    email = 'email@example.org'
  }

  options {
    # Add/override all init options for sentry-init, e.g
    max_breadcrumbs = 10
    # for more settings, @see https://docs.sentry.io/platforms/php/configuration/options/
  }
}

```

#### Configuring the scope

Sometimes it might be necessary to additionally configure the scope of a sentry event.  
For this, the static method `Plus\PlusSentry\Sentry::withScope($exception, $scopeCallback)` comes in play.  
The arguments are similar to `https://docs.sentry.io/platforms/php/enriching-events/scopes/#local-scopes`.
The first argument requires the exception to be thrown and the second is a callback, 
for where you can apply custom settings to the Sentry Scope.  
There is no need to additionally write `captureException` within that callback.
