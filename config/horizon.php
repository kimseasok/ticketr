<?php

return [
    'domain' => env('HORIZON_DOMAIN', null),

    'path' => env('HORIZON_PATH', 'horizon'),

    'use' => 'redis',

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    'middleware' => ['web'],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'fast_termination' => false,

    'waits' => [
        'redis:default' => 60,
    ],

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['default'],
                'balance' => 'auto',
                'maxProcesses' => 10,
                'maxTime' => 3600,
                'maxJobs' => 0,
                'sleep' => 3,
                'tries' => 3,
            ],
        ],
        'local' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['default'],
                'balance' => 'simple',
                'maxProcesses' => 3,
                'maxTime' => 0,
                'maxJobs' => 0,
                'sleep' => 3,
                'tries' => 3,
            ],
        ],
    ],
];
