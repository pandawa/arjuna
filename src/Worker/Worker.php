<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Worker;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Str;
use Pandawa\Arjuna\Broker\BrokerManager;
use Pandawa\Arjuna\Broker\ConsumedMessage;
use Pandawa\Arjuna\Broker\Consumer;
use Pandawa\Arjuna\Event\ConnectionExceptionOccurred;
use Pandawa\Arjuna\Event\MessageExceptionOccurred;
use Pandawa\Arjuna\Event\MessageProcessing;
use Pandawa\Arjuna\Event\MessagePushedToQueue;
use Pandawa\Arjuna\Job\ProcessConsumedMessageJob;
use Pandawa\Contracts\Bus\BusInterface;
use Pandawa\Contracts\Event\EventBusInterface;
use RuntimeException;
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class Worker
{
    public function __construct(
        protected readonly BusInterface $bus,
        protected readonly EventBusInterface $eventBus,
        protected readonly BrokerManager $broker,
        protected readonly ExceptionHandler $exceptions
    ) {
    }

    public function run(WorkerOptions $options): void
    {
        try {
            $consumer = $this->broker->driver($options->broker)->consumer();

            $consumer->subscribe($options->topics);

            $player = new WorkerPlayer($this->eventBus, $options, function (WorkerPlayer $player, WorkerOptions $options) use ($consumer) {
                try {
                    if (null !== $message = $this->getMessage($consumer, $options->timeout)) {
                        $this->processMessage($message, $options);
                    }
                } catch (Exception $e) {
                    $this->stopWorkerIfLostConnection($e, $player);

                    $this->eventBus->fire(
                        new ConnectionExceptionOccurred(
                            $options->broker,
                            $options->topics,
                            $e
                        )
                    );
                }
            });

            $player->play();
        } catch (Exception $e) {
            $this->eventBus->fire(new ConnectionExceptionOccurred($options->broker, $options->topics, $e));
        }
    }

    protected function processMessage(ConsumedMessage $message, WorkerOptions $options): void
    {
        $this->eventBus->fire(new MessageProcessing($options->broker, $message, $options->topics));

        try {
            if (null === $options->queue) {
                $this->bus->dispatchNow(new ProcessConsumedMessageJob($message, $options));

                return;
            }

            $this->bus->dispatch(
                (new ProcessConsumedMessageJob($message, $options))
                    ->onQueue($options->queue)
                    ->onConnection($options->queueConnection)
            );

            $this->eventBus->fire(new MessagePushedToQueue($options->broker, $message, $options->topics));
        } catch (Exception $e) {
            $this->eventBus->fire(
                new MessageExceptionOccurred(
                    $options->broker,
                    $message,
                    $e,
                    $options->topics
                )
            );
        }
    }

    protected function getMessage(Consumer $consumer, int $timeout): ?ConsumedMessage
    {
        try {
            return $consumer->consume($timeout);
        } catch (Exception $e) {
            $this->exceptions->report($e);

            throw $e;
        } catch (Throwable $e) {
            $this->exceptions->report($e = new RuntimeException($e->getMessage(), $e->getCode(), $e));

            throw $e;
        }
    }

    protected function stopWorkerIfLostConnection(Exception $e, ?WorkerPlayer $player): void
    {
        if (null !== $player && $this->causedByLostConnection($e)) {
            $player->stop();
        }
    }

    protected function causedByLostConnection(Throwable $e): bool
    {
        $message = strtolower(trim($e->getMessage()));

        return Str::contains($message, [
            'broker transport failure',
            'brokers are down',
            'connection refused',
            'disconnected',
        ]);
    }
}
