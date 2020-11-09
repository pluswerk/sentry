<?php

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
    'version' => '2.0.0',
    'constraints' =>[
        'depends' => [
            'typo3' => '10.0.0-11.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
