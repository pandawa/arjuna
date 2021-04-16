<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker\Adapter\Redis;

use Illuminate\Support\Str;
use Pandawa\Arjuna\Broker\ConsumedMessage;
use Pandawa\Arjuna\Broker\Consumer;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class RedisConsumerAdapter implements Consumer
{
    /**
     * @var Streamer;
     */
    private $redis;

    /**
     * @var string
     */
    private $group;

    /**
     * @var array
     */
    private $topics = [];

    /**
     * @var array
     */
    private $lastSeenIds = [];

    /**
     * @var ConsumedMessage[]
     */
    private $pending = [];

    public function __construct(Streamer $redis, string $group)
    {
        $this->redis = $redis;
        $this->group = $group;
    }

    public function subscribe(array $topics): void
    {
        $this->setupGroup($topics);
        $this->topics = $topics;
    }

    public function consume(int $timeout = 1000 * 120): ?ConsumedMessage
    {
        if ($this->hasPending()) {
            return $this->shift();
        }

        try {
            $messages = $this->redis->await($this->getConsumerName(), $this->buildStream(), $timeout);

            if (!$messages) {
                return null;
            }

            return $this->processMessages($messages);
        } catch (\Exception $e) {
            if (Str::contains(strtolower($e->getMessage()), ['read error on connection'])) {
                return null;
            }

            throw $e;
        }
    }

    private function shift(): ConsumedMessage
    {
        return array_shift($this->pending);
    }

    private function hasPending(): bool
    {
        return !empty($this->pending);
    }

    private function processMessages(array $messages): ConsumedMessage
    {
        foreach ($messages as $topic => $payload) {
            $messageId = null;

            foreach ($payload as $messageId => $message) {
                $this->pending[] = $this->decodeMessage($message);

                $this->redis->acknowledge($topic, $messageId);
            }

            $this->lastSeenIds[$topic] = $messageId ?? $this->redis->getNewEntriesKey();
        }

        return $this->shift();
    }

    private function decodeMessage(array $message): ConsumedMessage
    {
        return ConsumedMessage::fromArray(json_decode($message['message'], true));
    }

    private function buildStream(): array
    {
        return array_reduce($this->topics, function (?array $stream, $topic) {
            $lastSeenId = $this->lastSeenId ?? $this->redis->getNewEntriesKey();

            $this->lastSeenIds[$topic] = $lastSeenId;

            return array_merge($stream ?? [], [$topic => $lastSeenId]);
        });
    }

    private function getConsumerName(): string
    {
        return gethostname() . '-' . $this->group;
    }

    private function setupGroup(array $topics)
    {
        foreach ($topics as $topic) {
            if (!$this->redis->groupExists($this->group, $topic)) {
                $this->redis->createGroup($this->group, $topic);
            }
        }
    }
}
