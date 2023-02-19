<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Pandawa\Annotations\Console\AsConsole;
use Pandawa\Arjuna\Event\ConnectionExceptionOccurred;
use Pandawa\Arjuna\Event\MessageEvent;
use Pandawa\Arjuna\Event\MessageExceptionOccurred;
use Pandawa\Arjuna\Event\MessageProcessed;
use Pandawa\Arjuna\Event\MessageProcessing;
use Pandawa\Arjuna\Event\MessagePushedToQueue;
use Pandawa\Arjuna\Event\WorkerEvent;
use Pandawa\Arjuna\Event\WorkerPaused;
use Pandawa\Arjuna\Event\WorkerPlaying;
use Pandawa\Arjuna\Event\WorkerQuit;
use Pandawa\Arjuna\Event\WorkerResumed;
use Pandawa\Arjuna\Event\WorkerStopped;
use Pandawa\Arjuna\Messaging\Message;
use Pandawa\Arjuna\Worker\Worker;
use Pandawa\Arjuna\Worker\WorkerOptions;
use Pandawa\Contracts\Event\EventBusInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[AsConsole]
class ConsumeConsole extends Command
{
    /**
     * @var string
     */
    protected $signature = 'arjuna:consume
                            {topics : The topics that should be subscribe, use comma for multiple topics}
                            {--broker= : The name of the message broker to consume}
                            {--queue= : Process message into specific queue}
                            {--connection= : Process message into specific queue connection}
                            {--timeout=120000 : The number of milliseconds to wait when consuming message}';

    /**
     * @var string
     */
    protected $description = 'Start consuming message as a daemon';

    /**
     * Constructor.
     *
     * @param Worker $worker
     */
    public function __construct(
        protected readonly Worker $worker,
        protected readonly EventBusInterface $eventBus,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->listenForEvents();

        $this->worker->run($this->gatherWorkerOptions());
    }

    protected function gatherWorkerOptions(): WorkerOptions
    {
        return new WorkerOptions(
            explode(',', $this->argument('topics')),
            (int) $this->option('timeout'),
            $this->option('broker') ?? config('arjuna.default'),
            $this->option('queue'),
            $this->option('connection')
        );
    }

    protected function listenForEvents(): void
    {
        $this->eventBus->listen(MessageProcessing::class, function (MessageEvent $event) {
            $this->writeStatusForMessage($event->message, $event->topics, 'Processing', 'comment');
        });

        $this->eventBus->listen(MessageProcessed::class, function (MessageProcessed $event) {
            $this->writeStatusForMessage($event->message, $event->topics, 'Processed', 'info');
        });

        $this->eventBus->listen(MessagePushedToQueue::class, function (MessagePushedToQueue $event) {
            $this->writeStatusForMessage($event->message, $event->topics, 'Queued', 'info');
        });

        $this->eventBus->listen(MessageExceptionOccurred::class, function (MessageExceptionOccurred $event) {
            $this->writeStatusForMessage(
                $event->message,
                $event->topics,
                'Failed',
                'error',
                $event->exception->getMessage()
            );
        });

        $this->eventBus->listen(WorkerPlaying::class, function (WorkerPlaying $event) {
            $this->writeStatusForWorker($event->topics, 'Starting', 'info');
        });

        $this->eventBus->listen(ConnectionExceptionOccurred::class, function (ConnectionExceptionOccurred $event) {
            $this->writeStatusForWorker(
                $event->topics,
                'Failed',
                'error',
                $event->exception->getMessage()
            );
        });

        $this->eventBus->listen(WorkerPaused::class, function (WorkerPaused $event) {
            $this->writeStatusForWorker($event->topics, 'Paused', 'comment');
        });

        $this->eventBus->listen(WorkerResumed::class, function (WorkerResumed $event) {
            $this->writeStatusForWorker($event->topics, 'Resume', 'comment');
        });

        $this->eventBus->listen(WorkerStopped::class, function (WorkerEvent $event) {
            $this->writeStatusForWorker($event->topics, 'Stopped', 'error');
        });

        $this->eventBus->listen(WorkerQuit::class, function (WorkerEvent $event) {
            $this->writeStatusForWorker($event->topics, 'Quit', 'error');
        });
    }

    protected function writeStatusForMessage(Message $message, array $topics, string $status, string $type, string $reason = ''): void
    {
        $this->output->writeln(
            sprintf(
                "<{$type}>[%s][%s] %s %s</{$type}> %s %s",
                Carbon::now()->format('Y-m-d H:i:s'),
                $message->messageId(),
                str_pad("{$status}", 11),
                $message->messageName(),
                implode(',', $topics),
                $reason ? '- for ' . $reason : ''
            )
        );
    }

    protected function writeStatusForWorker(array $topics, string $status, string $type, string $message = ''): void
    {
        $this->output->writeln(
            sprintf(
                "<{$type}>[%s] %s %s %s</{$type}>",
                Carbon::now()->format('Y-m-d H:i:s'),
                str_pad("{$status}:", 11),
                implode(',', $topics),
                $message ? '- ' . $message : ''
            )
        );
    }
}
