<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker;

use Illuminate\Contracts\Container\Container;
use Pandawa\Annotations\DependencyInjection\Inject;
use Pandawa\Annotations\DependencyInjection\Injectable;
use Pandawa\Annotations\DependencyInjection\Type;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Injectable(alias: BrokerInterface::class)]
final class BrokerManager implements BrokerInterface
{
    /**
     * @var BrokerInterface[]
     */
    private array $brokers = [];

    /**
     * Constructor.
     *
     * @param Container $container
     * @param BrokerInterface[]  $brokers
     */
    public function __construct(
        #[Inject(Type::SERVICE, 'app')]
        private readonly Container $container,
        #[Inject(Type::TAG, 'arjunaBroker')]
        iterable $brokers = []
    ) {
        if ($brokers) {
            foreach ($brokers as $broker) {
                $this->extend($broker);
            }
        }
    }

    /**
     * Add a broker to manager.
     *
     * @param BrokerInterface $broker
     */
    public function extend(BrokerInterface $broker): void
    {
        $this->brokers[$broker->name()] = $broker;
    }

    /**
     * Send domain message to broker.
     *
     * @param string         $topic
     * @param string|int     $key
     * @param ProduceMessage $message
     */
    public function send(string $topic, $key, ProduceMessage $message): void
    {
        $this->driver()->send($topic, $key, $message);
    }

    /**
     * Get broker consumer.
     *
     * @return Consumer
     */
    public function consumer(): Consumer
    {
        return $this->driver()->consumer();
    }

    /**
     * Get broker driver.
     *
     * @param string|null $driver
     *
     * @return BrokerInterface
     */
    public function driver(string $driver = null): BrokerInterface
    {
        return $this->brokers[$driver ?? $this->getDefaultDriver()];
    }

    public function getDefaultDriver(): string
    {
        return $this->container->get('config')['arjuna.default'] ?: 'log';
    }

    public function name(): string
    {
        return $this->driver()->name();
    }
}
