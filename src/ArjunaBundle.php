<?php

declare(strict_types=1);

namespace Pandawa\Arjuna;

use Pandawa\Arjuna\Factory\ProduceMessageFactory;
use Pandawa\Arjuna\Factory\ProductMessageFactoryInterface;
use Pandawa\Arjuna\Middleware\ProduceMessageMiddleware;
use Pandawa\Bundle\ConsoleBundle\Plugin\ImportConsolePlugin;
use Pandawa\Bundle\DependencyInjectionBundle\Plugin\ImportInjectableAnnotationPlugin;
use Pandawa\Bundle\FoundationBundle\Plugin\ImportConfigurationPlugin;
use Pandawa\Component\Foundation\Application;
use Pandawa\Component\Foundation\Bundle\Bundle;
use Pandawa\Contracts\Event\EventBusInterface;
use Pandawa\Contracts\Foundation\HasPluginInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class ArjunaBundle extends Bundle implements HasPluginInterface
{
    public function configure(): void
    {
        $this->app->singleton(ProductMessageFactoryInterface::class, function (Application $app) {
            $config = $app['config'];
            $transformer = $config['arjuna']['transformer'];

            return new ProduceMessageFactory(
                $config['arjuna']['default_topic'],
                $app[$config['arjuna']['serializer']],
                 $transformer ? $app[$transformer] : null,
            );
        });

        $this->app->booted(function(Application $app) {
            /** @var EventBusInterface $eventBus */
            $eventBus = $app[EventBusInterface::class];
            $eventBus->mergeMiddlewares([ProduceMessageMiddleware::class]);
        });
    }

    public function plugins(): array
    {
        return [
            new ImportConfigurationPlugin(),
            new ImportInjectableAnnotationPlugin(),
            new ImportConsolePlugin(),
        ];
    }
}
