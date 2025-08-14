<?php

use Pluswerk\SentryTestExtension\Middleware\LastMiddlewareTestExceptionMiddleware;

return [
    'frontend' => [
        'pluswerk/sentry-test-extension/last-middleware-test-exception-middleware' => [
            'target' => LastMiddlewareTestExceptionMiddleware::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers',
                'typo3/cms-frontend/shortcut-and-mountpoint-redirect',
                'typo3/cms-core/response-propagation',
                'typo3/cms-frontend/output-compression',
                'typo3/cms-core/cache-timout',
            ],
        ],
    ],
];
