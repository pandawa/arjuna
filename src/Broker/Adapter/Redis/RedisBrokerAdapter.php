<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker\Adapter\Redis;

use Pandawa\Annotations\DependencyInjection\Inject;
use Pandawa\Annotations\DependencyInjection\Injectable;
use Pandawa\Annotations\DependencyInjection\Type;
use Pandawa\Arjuna\Broker\BrokerInterface;
use Pandawa\Arjuna\Broker\Consumer;
use Pandawa\Arjuna\Broker\ProduceMessage;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Injectable(tag: 'arjunaBroker')]
final class RedisBrokerAdapter implements BrokerInterface
{
    private Streamer $redis;

    public function __construct(
        #[Inject(Type::CONFIG, 'arjuna.drivers.redis.connection')]
        string $connection,
        #[Inject(Type::CONFIG, 'arjuna.group')]
        private readonly string $group,
        #[Inject(Type::CONFIG, 'arjuna.drivers.redis.retention_period')]
        private readonly int $retentionPeriod
    ) {
        $this->redis = new Streamer($connection, $group);
    }

    public function send(string $topic, $key, ProduceMessage $message): void
    {
        $key = is_int($key) ? $key : '*';

        $this->redis->add($topic, $key, $this->encodeMessage($message), $this->retentionPeriod);
    }

    public function consumer(): Consumer
    {
        return new RedisConsumerAdapter($this->redis, $this->group);
    }

    public function name(): string
    {
        return 'redis';
    }

    private function encodeMessage(ProduceMessage $message): array
    {
        return ['message' => json_encode($message->toArray())];
    }
}
