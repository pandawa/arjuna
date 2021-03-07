<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Job;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Pandawa\Arjuna\Broker\ConsumedMessage;
use Pandawa\Arjuna\Event\MessageProcessed;
use Pandawa\Arjuna\Worker\WorkerOptions;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ProcessConsumedMessageJob implements ShouldQueue
{
    use Queueable;

    /**
     * @var ConsumedMessage
     */
    private $message;

    /**
     * @var WorkerOptions
     */
    private $options;

    /**
     * Constructor.
     *
     * @param array         $message
     * @param WorkerOptions $options
     *
     * @throws Exception
     */
    public function __construct(array $message, WorkerOptions $options)
    {
        $this->message = ConsumedMessage::fromArray($message);
        $this->options = $options;
    }

    public function handle(Dispatcher $eventDispatcher): void
    {
        $eventDispatcher->dispatch($this->message);

        $eventDispatcher->dispatch(
            new MessageProcessed(
                $this->options->getBroker(),
                $this->message,
                $this->options->getTopics()
            )
        );
    }
}
