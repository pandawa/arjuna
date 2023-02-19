<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Pandawa\Arjuna\Broker\ConsumedMessage;
use Pandawa\Arjuna\Event\MessageProcessed;
use Pandawa\Arjuna\Stamp\DistributedMessageStamp;
use Pandawa\Arjuna\Worker\WorkerOptions;
use Pandawa\Component\Bus\Factory\EnvelopeFactory;
use Pandawa\Contracts\Event\EventBusInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class ProcessConsumedMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ConsumedMessage $message,
        private readonly WorkerOptions $options
    ) {
    }

    public function handle(EventBusInterface $eventBus, EnvelopeFactory $envelopeFactory): void
    {
        if (class_exists($class = $this->message->messageName())) {
            $message = $this->deserialize($class, $this->message->payload());
        } else {
            $message = $envelopeFactory->wrapByName($this->message->messageName(), $this->message->payload());
            $message = $message->without(DistributedMessageStamp::class);
        }

        $eventBus->fire($message);

        $eventBus->fire(
            new MessageProcessed(
                $this->options->broker,
                $this->message,
                $this->options->topics
            )
        );
    }

    private function deserialize(string $class, array $payload): mixed
    {
        return $this->serializer()->denormalize($payload, $class);
    }

    private function serializer(): DenormalizerInterface
    {
        return app(config('arjuna.serializer'));
    }
}
