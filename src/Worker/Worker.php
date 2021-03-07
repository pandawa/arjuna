<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Worker;

use Exception;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Pandawa\Arjuna\Broker\BrokerManager;
use Pandawa\Arjuna\Broker\ConsumedMessage;
use Pandawa\Arjuna\Broker\Consumer;
use Pandawa\Arjuna\Event\ConnectionExceptionOccurred;
use Pandawa\Arjuna\Event\MessageExceptionOccurred;
use Pandawa\Arjuna\Event\MessageProcessing;
use Pandawa\Arjuna\Job\ProcessConsumedMessageJob;
use Pandawa\Arjuna\Messaging\Message;
use Illuminate\Support\Str;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class Worker
{
    protected $broker;
    protected $exceptions;
    protected $dispatcher;
    protected $event;

    public function __construct(BusDispatcher $dispatcher, EventDispatcher $event, BrokerManager $broker, ExceptionHandler $exceptions)
    {
        $this->dispatcher = $dispatcher;
        $this->event = $event;
        $this->broker = $broker;
        $this->exceptions = $exceptions;
    }

    public function run(WorkerOptions $options): void
    {
        try {
            $consumer = $this->broker->driver($options->getBroker())->consumer();

            $consumer->subscribe($options->getTopics());

            $player = new WorkerPlayer($this->event, $options, function (WorkerPlayer $player, WorkerOptions $options) use ($consumer) {
                try {
                    if (null !== $message = $this->getMessage($consumer, $options->getTimeout())) {
                        $this->processMessage($message, $options);
                    }
                } catch (Exception $e) {
                    $this->stopWorkerIfLostConnection($e, $player);

                    $this->event->dispatch(
                        new ConnectionExceptionOccurred(
                            $options->getBroker(),
                            $options->getTopics(),
                            $e
                        )
                    );
                }
            });

            $player->play();
        } catch (Exception $e) {
            $this->event->dispatch(new ConnectionExceptionOccurred($options->getBroker(), $options->getTopics(), $e));
        }
    }

    protected function processMessage(Message $message, WorkerOptions $options): void
    {
        $this->event->dispatch(new MessageProcessing($options->getBroker(), $message, $options->getTopics()));

        try {
            $this->dispatcher->dispatch(
                (new ProcessConsumedMessageJob($message->toArray(), $options))
                    ->onQueue($options->getQueue())
                    ->onConnection($options->getQueueConnection())
            );
        } catch (Exception $e) {
            $this->event->dispatch(
                new MessageExceptionOccurred(
                    $options->getBroker(),
                    $message,
                    $e,
                    $options->getTopics()
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
            $this->exceptions->report($e = new FatalThrowableError($e));

            throw $e;
        }
    }

    protected function stopWorkerIfLostConnection(Exception $e, ?WorkerPlayer $player)
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
