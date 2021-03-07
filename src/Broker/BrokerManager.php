<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker;

use Illuminate\Contracts\Container\Container;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class BrokerManager implements Broker
{
    /**
     * @var Broker[]
     */
    private $brokers = [];

    /**
     * @var Container
     */
    private $container;

    /**
     * Constructor.
     *
     * @param Container $container
     * @param Broker[]  $brokers
     */
    public function __construct(Container $container, $brokers = [])
    {
        $this->container = $container;

        if ($brokers) {
            foreach ($brokers as $broker) {
                $this->extend($broker);
            }
        }
    }

    /**
     * Add a broker to manager.
     *
     * @param Broker $broker
     */
    public function extend(Broker $broker): void
    {
        $this->brokers[$broker->name()] = $broker;
    }

    /**
     * Send domain message to broker.
     *
     * @param string         $topic
     * @param string         $key
     * @param ProduceMessage $message
     */
    public function send(string $topic, string $key, ProduceMessage $message): void
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
     * @return Broker
     */
    public function driver(string $driver = null): Broker
    {
        return $this->brokers[$driver ?? $this->getDefaultDriver()];
    }

    public function getDefaultDriver(): string
    {
        return $this->container->get('config')['arjuna.driver'] ?: 'kafka';
    }

    public function name(): string
    {
        return $this->driver()->name();
    }
}
