<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker\Adapter\Kafka;

use Pandawa\Arjuna\Broker\ConsumedMessage;
use Pandawa\Arjuna\Broker\Consumer;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RuntimeException;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class KafkaConsumerAdapter implements Consumer
{
    private KafkaConsumer $consumer;

    /**
     * Constructor.
     *
     * @param Conf $conf
     */
    public function __construct(Conf $conf)
    {
        $this->consumer = new KafkaConsumer($conf);
    }

    public function subscribe(array $topics): void
    {
        $this->consumer->subscribe($topics);
    }

    public function consume(int $timeout = 1000 * 120): ?ConsumedMessage
    {
        $message = $this->consumer->consume($timeout);

        if (RD_KAFKA_RESP_ERR_NO_ERROR === $message->err) {
            $decodeMessage = $this->decodeMessage($message);

            $this->consumer->commitAsync($message);

            return $decodeMessage;
        }

        if (RD_KAFKA_RESP_ERR__PARTITION_EOF === $message->err || RD_KAFKA_RESP_ERR__TIMED_OUT === $message->err) {
            return null;
        }

        throw new RuntimeException($message->errstr(), $message->err);
    }

    private function decodeMessage(Message $message): ConsumedMessage
    {
        return ConsumedMessage::fromArray(json_decode($message->payload, true));
    }
}
