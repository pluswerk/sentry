<?php

use Composer\InstalledVersions;

/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Pluswerk: Sentry Client',
    'description' => '',
    'category' => 'service',
    'author' => 'Pluswerk AG',
    'author_email' => 'stefan.lamm@pluswerk.ag',
    'state' => 'stable',
    'version' => InstalledVersions::getPrettyVersion('pluswerk/sentry'),
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
