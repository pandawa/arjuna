<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Pandawa\Arjuna\Broker\BrokerInterface;
use Pandawa\Arjuna\Broker\ProduceMessage;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class ProduceMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ProduceMessage $message)
    {
    }

    public function handle(BrokerInterface $broker): void
    {
        $broker->send($this->message->getProduceTopic(), $this->message->getProduceKey(), $this->message);
    }
}
