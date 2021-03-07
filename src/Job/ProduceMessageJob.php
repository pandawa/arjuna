<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Pandawa\Arjuna\Broker\Broker;
use Pandawa\Arjuna\Broker\ProduceMessage;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ProduceMessageJob implements ShouldQueue
{
    use Queueable;

    private $message;

    public function __construct(ProduceMessage $message)
    {
        $this->message = $message;
    }

    public function handle(Broker $broker): void
    {
        $broker->send($this->message->getProduceTopic(), $this->message->getProduceKey(), $this->message);
    }
}
