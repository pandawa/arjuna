<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker\Adapter\Kafka;

use Pandawa\Annotations\DependencyInjection\Inject;
use Pandawa\Annotations\DependencyInjection\Injectable;
use Pandawa\Annotations\DependencyInjection\Type;
use Pandawa\Arjuna\Broker\BrokerInterface;
use Pandawa\Arjuna\Broker\Consumer;
use Pandawa\Arjuna\Broker\ProduceMessage;
use Pandawa\Arjuna\Messaging\Message;
use RdKafka\Conf;
use RdKafka\Producer;
use RuntimeException;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Injectable(tag: 'arjunaBroker')]
final class KafkaBrokerAdapter implements BrokerInterface
{
    private Producer $producer;

    private KafkaConsumerAdapter $consumer;

    private Conf $config;

    private array $options = [];

    /**
     * Constructor.
     *
     * @param  string  $brokers
     * @param  string  $group
     * @param  string  $compressionType
     * @param  bool  $autocommit
     * @param  bool  $debug
     */
    public function __construct(
        #[Inject(Type::CONFIG, 'arjuna.drivers.kafka.brokers')]
        string $brokers,
        #[Inject(Type::CONFIG, 'arjuna.group')]
        string $group,
        #[Inject(Type::CONFIG, 'arjuna.drivers.kafka.compression_type')]
        string $compressionType,
        #[Inject(Type::CONFIG, 'arjuna.drivers.kafka.autocommit')]
        bool $autocommit,
        #[Inject(Type::CONFIG, 'arjuna.drivers.kafka.debug')]
        bool $debug
    ) {
        $this->options = [
            'brokers' => $brokers,
            'group' => $group,
            'compression_type' => $compressionType,
            'autocommit' => $autocommit,
            'debug' => $debug,
        ];
    }

    public function name(): string
    {
        return 'kafka';
    }

    public function send(string $topic, $key, ProduceMessage $message): void
    {
        $topic = $this->producer()->newTopic($topic);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $this->createStringPayload($message), $key);
        $this->producer()->poll(0);

        $this->flush();
    }

    public function consumer(): Consumer
    {
        if (null === $this->consumer) {
            $this->consumer = new KafkaConsumerAdapter($this->config());
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

    private function createConfig(
        string $brokers,
        string $group,
        string $compressionType,
        bool $autoCommit,
        bool $debug
    ): Conf {
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

        $this->producer = new Producer($this->config());

        return $this->producer;
    }

    private function config(): Conf
    {
        if (!class_exists('\RdKafka\Conf')) {
            throw new RuntimeException('Please install rdkafka extension to use kafka broker.');
        }

        if (null !== $this->config) {
            return $this->config;
        }

        $this->config = $this->createConfig(
            $this->options['brokers'],
            $this->options['group'],
            $this->options['compression_type'],
            $this->options['autocommit'],
            $this->options['debug']
        );

        return $this->config;
    }
}
