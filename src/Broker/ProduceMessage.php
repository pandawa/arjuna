<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker;

use Pandawa\Arjuna\Messaging\DomainMessage;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ProduceMessage extends DomainMessage
{
    protected string $produceKey;

    protected string $produceTopic;

    public static function fromArray(array $data): static
    {
        $message = parent::fromArray($data);
        $message->produceKey = (string) $data['produce_key'];
        $message->produceTopic = (string) $data['produce_topic'];

        return $message;
    }

    public function getProduceKey(): string
    {
        return $this->produceKey;
    }

    public function getProduceTopic(): string
    {
        return $this->produceTopic;
    }
}
