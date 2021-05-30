<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker\Adapter\Log;

use Pandawa\Arjuna\Broker\Broker;
use Pandawa\Arjuna\Broker\Consumer;
use Pandawa\Arjuna\Broker\ProduceMessage;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class LogBrokerAdapter implements Broker
{
    public function send(string $topic, $key, ProduceMessage $message): void
    {
        logger(sprintf('Message with topic "%s" and key "%s" was sent.', $topic, $key));
    }

    public function consumer(): Consumer
    {
        return new LogConsumer();
    }

    public function name(): string
    {
        return 'log';
    }
}
