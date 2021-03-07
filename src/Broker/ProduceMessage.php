<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker;

use Exception;
use Pandawa\Arjuna\Messaging\DomainMessage;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ProduceMessage extends DomainMessage
{
    /**
     * @var string
     */
    protected $produceKey;

    /**
     * @var string
     */
    protected $produceTopic;

    /**
     * @param array $data
     *
     * @return DomainMessage|static
     * @throws Exception
     */
    public static function fromArray(array $data): DomainMessage
    {
        $message = parent::fromArray($data);
        $message->produceKey = $data['produce_key'];
        $message->produceTopic = $data['produce_topic'];

        return $message;
    }

    /**
     * @return string
     */
    public function getProduceKey(): string
    {
        return (string) $this->produceKey;
    }

    /**
     * @return string
     */
    public function getProduceTopic(): string
    {
        return $this->produceTopic;
    }
}
