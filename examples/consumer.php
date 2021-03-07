<?php

use Illuminate\Contracts\Events\Dispatcher as EventDispatcherContract;
use Pandawa\Arjuna\Worker\Worker;
use Pandawa\Arjuna\Worker\WorkerOptions;

$app = require_once __DIR__ . '/bootstrap.php';

$eventEmitter = $app->get(EventDispatcherContract::class);

$eventEmitter->listen(\Pandawa\Arjuna\Event\WorkerPlaying::class, function () {
    echo "Consuming...\n";
});

$eventEmitter->listen(\Pandawa\Arjuna\Event\WorkerStopped::class, function () {
    echo "Stopped...\n";
});

$eventEmitter->listen(\Pandawa\Arjuna\Event\MessageProcessing::class, function (\Pandawa\Arjuna\Event\MessageEvent $event) {
    echo "Processing " . json_encode($event->getMessage()->toArray()) . "\n";
});

$eventEmitter->listen(\Pandawa\Arjuna\Event\ConnectionExceptionOccurred::class, function (\Pandawa\Arjuna\Event\ConnectionExceptionOccurred $event) {
    echo "Error " . implode(', ', $event->getTopics()) . " - " . $event->getException()->getMessage() . "\n";
});

$eventEmitter->listen(ConsumedUserRegistered::class, function (ConsumedUserRegistered $event) {
    echo "Raised event with payload " . json_encode($event->payload()->all()) . "\n";
});

$app
    ->get(Worker::class)
    ->run(
        new WorkerOptions(['v2.order'], 10 * 1000, 'kafka', null, null)
    );
