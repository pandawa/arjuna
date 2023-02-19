<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker\Adapter\Redis;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Facades\Redis;
use RuntimeException;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class Streamer
{
    public const GROUPS = 'GROUPS';
    public const CREATE = 'CREATE';
    public const NEW_ENTRIES = '>';

    private Connection|PhpRedisConnection|null $redis = null;

    public function __construct(
        private readonly string $connection,
        private readonly string $group
    ) {
    }

    public function add(string $key, string $id, array $message, int $retentionPeriod): void
    {
        $this->redis()->xAdd($key, $id, $message);
        $this->redis()->expire($key, $retentionPeriod * 60 * 60 * 24);
    }

    public function acknowledge(string $topic, string $id): void
    {
        $result = $this->redis()->xAck($topic, $this->group, [$id]);

        if ($result === 0) {
            throw new RedisException(sprintf('Could not acknowledge message with ID %s', $id));
        }
    }

    public function await(string $consumer, array $streams, int $timeout = 0): ?array
    {
        if (false === $message = $this->redis()->xReadGroup($this->group, $consumer, $streams, null, $timeout)) {
            throw new RuntimeException('disconnected');
        }

        return $message;
    }

    public function groups(string $topic): array
    {
        $result = $this->redis()->xInfo(self::GROUPS, $topic);
        if (!$result) {
            throw new RedisException(sprintf('No results for stream %s', $topic));
        }

        return $result;
    }

    public function groupExists(string $name, string $topic): bool
    {
        try {
            $groups = $this->groups($topic);
        } catch (RedisException $ex) {
            return false;
        }

        foreach ($groups as $group) {
            if ($group['name'] === $name) {
                return true;
            }
        }

        return false;
    }

    public function createGroup(
        string $name,
        string $topic,
        string $from = '0',
        bool $createStreamIfNotExists = true
    ): bool {
        if ($createStreamIfNotExists) {
            return $this->redis()->xGroup(self::CREATE, $topic, $name, $from, 'MKSTREAM');
        }

        return $this->redis()->xGroup(self::CREATE, $topic, $name, $from);
    }

    public function getNewEntriesKey(): string
    {
        return self::NEW_ENTRIES;
    }

    private function redis(): Connection|PhpRedisConnection
    {
        if (null !== $redis = $this->redis) {
            return $redis;
        }

        if (!class_exists('\Pandawa\Bundle\RedisBundle\RedisBundle')) {
            throw new RuntimeException('Please install "pandawa/redis-bundle" to use arjuna with Redis broker.');
        }

        $this->redis = Redis::connection($this->connection);

        $client = $this->redis->client();

        if ($client instanceof \Predis\Client) {
            throw new RuntimeException('Predis driver is not supported, please use phpredis driver instead.');
        }

        if ($client instanceof \Redis) {
            $this->redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        }

        return $this->redis;
    }
}
