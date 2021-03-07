<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Listener;

use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Container\Container;
use Pandawa\Arjuna\Broker\Broker;
use Pandawa\Arjuna\Broker\ProduceMessage;
use Pandawa\Arjuna\Factory\ProduceMessageFactory;
use Pandawa\Arjuna\Job\ProduceMessageJob;
use Pandawa\Arjuna\Messaging\ProduceMessage as ProduceMessageContract;
use Pandawa\Component\Message\AbstractMessage;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class MessageProducer
{
    /**
     * @var ProduceMessageFactory
     */
    private $factory;

    /**
     * @var Broker
     */
    private $broker;

    /**
     * @var Dispatcher
     */
    private $dispatcher;
    /**
     * @var Container
     */
    private $container;

    /**
     * Constructor.
     *
     * @param Container             $container
     * @param ProduceMessageFactory $factory
     * @param Broker                $broker
     * @param Dispatcher            $dispatcher
     */
    public function __construct(Container $container, ProduceMessageFactory $factory, Broker $broker, Dispatcher $dispatcher)
    {
        $this->container = $container;
        $this->factory = $factory;
        $this->broker = $broker;
        $this->dispatcher = $dispatcher;
    }


    public function handle($eventName, $events): void
    {
        foreach ($events as $event) {
            if ($event instanceof AbstractMessage && $event instanceof ProduceMessageContract) {
                foreach ($this->factory->createFromMessage($event) as $message) {
                    $this->produce($message);
                }
            }
        }
    }

    /**
     * Produce domain message to broker.
     *
     * @param ProduceMessage $message
     */
    private function produce(ProduceMessage $message): void
    {
        if ($queue = $this->produceWithQueue()) {
            $this->produceThroughQueue($message, $queue, $this->produceWithQueueConnection());

            return;
        }

        $this->produceNow($message);
    }

    /**
     * Produce message immediately.
     *
     * @param ProduceMessage $message
     */
    private function produceNow(ProduceMessage $message): void
    {
        $this->broker->send($message->getProduceTopic(), $message->getProduceKey(), $message);
    }

    /**
     * Produce message through queue.
     *
     * @param ProduceMessage $message
     * @param string         $queue
     * @param string         $connection
     */
    private function produceThroughQueue(ProduceMessage $message, $queue, $connection): void
    {
        $this->dispatcher->dispatch(
            (new ProduceMessageJob($message))
                ->onQueue($queue)
                ->onConnection($connection)
        );
    }

    private function produceWithQueue()
    {
        return $this->config('arjuna.produce.queue');
    }

    private function produceWithQueueConnection()
    {
        return $this->config('arjuna.produce.connection') ?: $this->config('queue.default');
    }

    private function config(string $key)
    {
        return $this->container->get('config')->get($key);
    }
}
