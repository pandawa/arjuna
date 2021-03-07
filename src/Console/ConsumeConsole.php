<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Pandawa\Arjuna\Event\ConnectionExceptionOccurred;
use Pandawa\Arjuna\Event\MessageEvent;
use Pandawa\Arjuna\Event\MessageExceptionOccurred;
use Pandawa\Arjuna\Event\MessageProcessed;
use Pandawa\Arjuna\Event\MessageProcessing;
use Pandawa\Arjuna\Event\WorkerEvent;
use Pandawa\Arjuna\Event\WorkerPaused;
use Pandawa\Arjuna\Event\WorkerPlaying;
use Pandawa\Arjuna\Event\WorkerQuit;
use Pandawa\Arjuna\Event\WorkerResumed;
use Pandawa\Arjuna\Event\WorkerStopped;
use Pandawa\Arjuna\Messaging\Message;
use Pandawa\Arjuna\Worker\Worker;
use Pandawa\Arjuna\Worker\WorkerOptions;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
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
     * @var Worker
     */
    protected $worker;

    /**
     * Constructor.
     *
     * @param Worker $worker
     */
    public function __construct(Worker $worker)
    {
        parent::__construct();

        $this->worker = $worker;
    }

    public function handle(): void
    {
        $this->listenForEvents();

        $this->worker->run($this->gatherWorkerOptions());
    }

    protected function gatherWorkerOptions()
    {
        return new WorkerOptions(
            explode(',', $this->argument('topics')),
            (int) $this->option('timeout'),
            $this->option('broker'),
            $this->option('queue'),
            $this->option('connection')
        );
    }

    protected function listenForEvents(): void
    {
        $this->laravel['events']->listen(MessageProcessing::class, function (MessageEvent $event) {
            $this->writeStatusForMessage($event->getMessage(), $event->getTopics(), 'Processing', 'comment');
        });

        $this->laravel['events']->listen(MessageProcessed::class, function (MessageEvent $event) {
            $this->writeStatusForMessage($event->getMessage(), $event->getTopics(), 'Processed', 'info');
        });

        $this->laravel['events']->listen(MessageExceptionOccurred::class, function (MessageEvent $event) {
            $this->writeStatusForMessage($event->getMessage(), $event->getTopics(), 'Failed', 'error');
        });

        $this->laravel['events']->listen(WorkerPlaying::class, function (WorkerEvent $event) {
            $this->writeStatusForWorker($event->getTopics(), 'Starting', 'info');
        });

        $this->laravel['events']->listen(ConnectionExceptionOccurred::class, function (ConnectionExceptionOccurred $event) {
            $this->writeStatusForWorker($event->getTopics(), 'Failed', 'error');
        });

        $this->laravel['events']->listen(WorkerPaused::class, function (WorkerEvent $event) {
            $this->writeStatusForWorker($event->getTopics(), 'Paused', 'comment');
        });

        $this->laravel['events']->listen(WorkerResumed::class, function (WorkerEvent $event) {
            $this->writeStatusForWorker($event->getTopics(), 'Resume', 'comment');
        });

        $this->laravel['events']->listen(WorkerStopped::class, function (WorkerEvent $event) {
            $this->writeStatusForWorker($event->getTopics(), 'Stopped', 'error');
        });

        $this->laravel['events']->listen(WorkerQuit::class, function (WorkerEvent $event) {
            $this->writeStatusForWorker($event->getTopics(), 'Quit', 'error');
        });
    }

    protected function writeStatusForMessage(Message $message, array $topics, string $status, string $type): void
    {
        $this->output->writeln(
            sprintf(
                "<{$type}>[%s][%s] %s</{$type}> %s for %s",
                Carbon::now()->format('Y-m-d H:i:s'),
                $message->messageId(),
                str_pad("{$status}:", 11),
                $message->messageName(),
                implode(',', $topics)
            )
        );
    }

    protected function writeStatusForWorker(array $topics, string $status, string $type): void
    {
        $this->output->writeln(
            sprintf(
                "<{$type}>[%s][%s] %s</{$type}>",
                Carbon::now()->format('Y-m-d H:i:s'),
                str_pad("{$status}:", 11),
                implode(',', $topics)
            )
        );
    }
}
