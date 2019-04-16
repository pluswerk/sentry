<?php
declare(strict_types=1);

/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Pluswerk: Sentry Client',
    'description' => '',
    'category' => 'service',
    'author' => 'Pluswerk AG',
    'author_email' => 'christian.benthake@pluswerk.ag',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => '0',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.0',
    'constraints' =>[
        'depends' => [
            'typo3' => '8.0.0-9.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
