<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker\Adapter\Redis;

use Pandawa\Arjuna\Broker\Broker;
use Pandawa\Arjuna\Broker\Consumer;
use Pandawa\Arjuna\Broker\ProduceMessage;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class RedisBrokerAdapter implements Broker
{
    /**
     * @var Streamer
     */
    private $redis;

    /**
     * @var string
     */
    private $group;

    public function __construct(string $connection, string $group)
    {
        $this->redis = new Streamer($connection, $group);
        $this->group = $group;
    }

    public function send(string $topic, string $key, ProduceMessage $message): void
    {
        $this->redis->add($topic, '*', $this->encodeMessage($message));
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
