<?php

declare(strict_types=1);

namespace Pandawa\Arjuna;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Events\Dispatcher;
use Pandawa\Arjuna\Dispatcher\EventDispatcher;
use Pandawa\Arjuna\Listener\MessageProducer;
use Pandawa\Arjuna\Mapper\RegistryMapper;
use Pandawa\Component\Module\AbstractModule;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class PandawaArjunaModule extends AbstractModule
{
    protected function build(): void
    {
        $this->publishResources();
        $this->listenAllEvent();

        $this->app->singleton(RegistryMapper::class, function ($app) {
            return new RegistryMapper($app['config']->get('arjuna.event_mappers', []));
        });
    }

    protected function init(): void
    {
        $this->app->singleton('events', function ($app) {
            return (new EventDispatcher($app))->setQueueResolver(function () use ($app) {
                return $app->make(QueueFactoryContract::class);
            });
        });

        $this->app->alias('events', DispatcherContract::class);
        $this->app->alias('events', Dispatcher::class);
    }

    private function listenAllEvent(): void
    {
        $this->dispatcher()->listen('*', MessageProducer::class);
    }

    private function publishResources(): void
    {
        $this->publishes(
            [
                __DIR__.'/Resources/config.php' => $this->app['path.config'].DIRECTORY_SEPARATOR.'arjuna.php',
            ],
            'arjuna'
        );
    }

    private function dispatcher(): DispatcherContract
    {
        return $this->app[DispatcherContract::class];
    }
}
