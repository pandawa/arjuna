<?php

use Illuminate\Support\Str;

return [
    'default' => env('ARJUNA_BROKER', 'kafka'),

    'produce' => [
        'queue'      => env('ARJUNA_PRODUCE_QUEUE', false),
        'connection' => env('ARJUNA_PRODUCE_QUEUE_CONNECTION'),
    ],

    'drivers' => [
        'kafka' => [
            'brokers'          => env('KAFKA_BROKERS'),
            'group'            => env('KAFKA_GROUP', Str::snake(config('app.name'))),
            'compression_type' => env('KAFKA_COMPRESSION', 'snappy'),
            'autocommit'       => env('KAFKA_AUTOCOMMIT', false),
            'debug'            => env('KAFKA_DEBUG', false),
        ],
    ],
];
