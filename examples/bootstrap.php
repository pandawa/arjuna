<?php

use Illuminate\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcherContract;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcherContract;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Arr;
use Pandawa\Arjuna\Broker\Adapter\Kafka\KafkaBrokerAdapter;
use Pandawa\Arjuna\Broker\Adapter\Redis\RedisBrokerAdapter;
use Pandawa\Arjuna\Broker\Broker;
use Pandawa\Arjuna\Broker\BrokerManager;
use Pandawa\Arjuna\Dispatcher\EventDispatcher;
use Pandawa\Arjuna\Listener\MessageProducer;
use Pandawa\Arjuna\Mapper\RegistryMapper;
use Pandawa\Arjuna\Worker\Worker;
use Pandawa\Component\Transformer\TransformerRegistry;
use Pandawa\Component\Transformer\TransformerRegistryInterface;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/UserRegistered.php';
require __DIR__ . '/ConsumedUserRegistered.php';

$app = new \Illuminate\Foundation\Application(__DIR__);
$app->singleton('config', function () {
    return new Illuminate\Config\Repository([
        'arjuna' => [
            'driver' => 'redis',
        ]
    ]);
});

$app->singleton(BusDispatcherContract::class, function () use ($app) {
    return new BusDispatcher($app);
});
$app->singleton(RegistryMapper::class, function () {
    return new RegistryMapper(
        [
            // Try use SelfProduceMessage
            /*'user-registered' => [
                'available_versions' => [1, 2],
                'produce_topic' => 'order',
                'produce_key' => 'person.id',
                'attributes' => [
                    'person.id',
                    'person.full_name' => [
                        'from' => 'person.name',
                    ],
                    'person.age' => [
                        'versions' => [2],
                    ],
                    'position' => [
                        'versions' => [1],
                    ],
                ]
            ],*/
        ]
    );
});
$app->singleton(TransformerRegistryInterface::class, function () use ($app) {
    return new TransformerRegistry(new \Illuminate\Foundation\Application(), []);
});

$app->singleton('redis-con', function ($app) {
    return new RedisManager($app, 'phpredis', [
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ]
    ]);
});

$app->singleton(Broker::class, function () use ($app) {
    return new BrokerManager($app, [
        new KafkaBrokerAdapter(
            '127.0.0.1:9092',
            'default',
            'snappy',
            false,
            false
        ),
        new RedisBrokerAdapter('default', 'default')
    ]);
});

$app->singleton(EventDispatcherContract::class, function () use ($app) {
    $eventEmitter = new EventDispatcher($app);
    $eventEmitter->listen('*', MessageProducer::class);

    return $eventEmitter;
});


$app->singleton(Worker::class, function () use ($app) {
    return new Worker(
        $app->get(BusDispatcherContract::class),
        $app->get(EventDispatcherContract::class),
        $app->get(Broker::class),
        new Handler($app)
    );
});

return $app;
