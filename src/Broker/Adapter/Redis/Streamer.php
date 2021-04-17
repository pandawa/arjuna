<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker\Adapter\Redis;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Facades\Redis;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class Streamer
{
    public const GROUPS = 'GROUPS';
    public const CREATE = 'CREATE';
    public const NEW_ENTRIES = '>';

    /**
     * @var Connection|PhpRedisConnection
     */
    private $redis;

    /**
     * @var string
     */
    private $connection;

    /**
     * @var string
     */
    private $group;

    public function __construct(string $connection, string $group)
    {
        $this->connection = $connection;
        $this->group = $group;
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
        return $this->redis()->xReadGroup($this->group, $consumer, $streams, null, $timeout);
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

    public function createGroup(string $name, string $topic, string $from = '0', bool $createStreamIfNotExists = true): bool
    {
        if ($createStreamIfNotExists) {
            return $this->redis()->xGroup(self::CREATE, $topic, $name, $from, 'MKSTREAM');
        }

        return $this->redis()->xGroup(self::CREATE, $topic, $name, $from);
    }

    public function getNewEntriesKey(): string
    {
        return self::NEW_ENTRIES;
    }

    /**
     * @return Connection|PhpRedisConnection
     */
    private function redis()
    {
        if (null !== $redis = $this->redis) {
            return $redis;
        }

        $this->redis = Redis::connection($this->connection);
        $this->redis->setOption(\Redis::OPT_PREFIX, '');

        return $this->redis;
    }
}
