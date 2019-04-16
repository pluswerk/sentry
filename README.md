![PHP Version](https://img.shields.io/packagist/php-v/pluswerk/sentry.svg?style=flat-square)
[![Packagist Release](https://img.shields.io/packagist/v/pluswerk/sentry.svg)](https://packagist.org/packages/pluswerk/sentry)
[![Travis](https://img.shields.io/travis/pluswerk/sentry.svg?style=flat-square)](https://travis-ci.org/pluswerk/sentry)
[![GitHub License](https://img.shields.io/github/license/pluswerk/sentry.svg?style=flat-square)](https://github.com/pluswerk/sentry/blob/master/LICENSE.txt)
[![Build Status](https://travis-ci.org/pluswerk/sentry.svg?branch=master)](https://travis-ci.org/pluswerk/sentry)

# Pluswerk TYPO3 Sentry PHP client
Usage:

Add your Sentry URL to your Local Configuration like this:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sentry'] = [
        'dsn' => 'https://xyz@sentry.io/12345',
];
```

Require via
```
composer require pluswerk/sentry
```
