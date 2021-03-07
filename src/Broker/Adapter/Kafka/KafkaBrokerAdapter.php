<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker\Adapter\Kafka;

use Pandawa\Arjuna\Broker\Broker;
use Pandawa\Arjuna\Broker\ProduceMessage;
use Pandawa\Arjuna\Broker\Consumer;
use Pandawa\Arjuna\Messaging\Message;
use RdKafka\Conf;
use RdKafka\Producer;
use RuntimeException;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class KafkaBrokerAdapter implements Broker
{
    /**
     * @var Producer
     */
    private $producer;

    /**
     * @var KafkaConsumerAdapter
     */
    private $consumer;

    /**
     * @var Conf
     */
    private $config;

    /**
     * Constructor.
     *
     * @param string $brokers
     * @param string $group
     * @param string $compressionType
     * @param bool   $autocommit
     * @param bool   $debug
     */
    public function __construct(string $brokers, string $group, string $compressionType, bool $autocommit, bool $debug)
    {
        $this->config = $this->createConfig($brokers, $group, $compressionType, $autocommit, $debug);
    }

    public function name(): string
    {
        return 'kafka';
    }

    public function send(string $topic, string $key, ProduceMessage $message): void
    {
        $topic = $this->producer()->newTopic($topic);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $this->createStringPayload($message), $key);
        $this->producer()->poll(0);

        $this->flush();
    }

    public function consumer(): Consumer
    {
        if (null === $this->consumer) {
            $this->consumer = new KafkaConsumerAdapter($this->config);
        }

        return $this->consumer;
    }

    private function flush(int $timeout = 1000 * 60): void
    {
        $result = $this->producer()->flush($timeout);

        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
            throw new RuntimeException('Unable to perform flush');
        }
    }

    private function createStringPayload(Message $message): string
    {
        return json_encode($message->toArray());
    }

    private function createConfig(string $brokers, string $group, string $compressionType, bool $autoCommit, bool $debug): Conf
    {
        $conf = new Conf();

        $conf->set('metadata.broker.list', $brokers);
        $conf->set('group.id', $group);
        $conf->set('compression.type', $compressionType);
        $conf->set('auto.offset.reset', 'smallest');
        $conf->set('enable.auto.commit', $autoCommit ? 'true' : 'false');
        $conf->setErrorCb(function ($consumer, $errCode, $errMsg) {
            if ($errMsg && $errCode) {
                throw new KafkaException($errMsg, $errCode);
            }
        });

        if (true === $debug) {
            $conf->set('log_level', (string) LOG_DEBUG);
            $conf->set('debug', 'all');
        }

        return $conf;
    }

    private function producer(): Producer
    {
        if ($producer = $this->producer) {
            return $producer;
        }

        $this->producer = new Producer($this->config);

        return $this->producer;
    }
}
