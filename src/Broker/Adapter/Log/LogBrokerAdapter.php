<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker\Adapter\Log;

use Pandawa\Annotations\DependencyInjection\Injectable;
use Pandawa\Arjuna\Broker\BrokerInterface;
use Pandawa\Arjuna\Broker\Consumer;
use Pandawa\Arjuna\Broker\ProduceMessage;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Injectable(tag: 'arjunaBroker')]
class LogBrokerAdapter implements BrokerInterface
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
