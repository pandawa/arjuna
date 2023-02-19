<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Middleware;

use App\Post\Event\PostPublished;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Pandawa\Arjuna\Broker\BrokerInterface;
use Pandawa\Arjuna\Broker\ProduceMessage;
use Pandawa\Arjuna\Factory\ProductMessageFactoryInterface;
use Pandawa\Arjuna\Job\ProduceMessageJob;
use Pandawa\Component\Event\NoneObjectEvent;
use Pandawa\Contracts\Bus\BusInterface;
use Pandawa\Contracts\Bus\Envelope;
use Pandawa\Contracts\Bus\MiddlewareInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class ProduceMessageMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ProductMessageFactoryInterface $messageFactory,
        private readonly BrokerInterface $broker,
        private readonly BusInterface $bus,
        private readonly Repository $config,
    ) {
    }

    public function handle(Envelope $envelope, Closure $next): mixed
    {
        if (!$envelope->message instanceof NoneObjectEvent) {
            foreach ($this->messageFactory->createFromMessage($envelope) as $message) {
                $this->produce($message);
            }
        }

        return $next($envelope);
    }

    private function produce(ProduceMessage $message): void
    {
        if ($queue = $this->produceWithQueue()) {
            $this->produceThroughQueue($message, $queue, $this->produceWithQueueConnection());

            return;
        }

        $this->produceNow($message);
    }

    private function produceThroughQueue(ProduceMessage $message, string|bool|null $queue, ?string $connection): void
    {
        $this->bus->dispatch(
            (new ProduceMessageJob($message))
                ->onQueue($queue)
                ->onConnection($connection)
        );
    }

    private function produceNow(ProduceMessage $message): void
    {
        $this->broker->send($message->getProduceTopic(), $message->getProduceKey(), $message);
    }

    private function produceWithQueue(): string|bool|null
    {
        return $this->config->get('arjuna.produce.queue');
    }

    private function produceWithQueueConnection(): string|null
    {
        return $this->config->get('arjuna.produce.connection', $this->config->get('queue.default'));
    }
}
