<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Mapper;

use Pandawa\Component\Message\AbstractMessage;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class EventMapper
{
    /**
     * @var string
     */
    private $eventName;

    /**
     * @var string
     */
    private $produceTopic;

    /**
     * @var string
     */
    private $produceKey;

    /**
     * @var array
     */
    private $availableVersions = [];

    /**
     * @var array
     */
    private $attributes = [];

    public static function createFromArray(array $data): EventMapper
    {
        $mapper = new static();
        $mapper->eventName = $data['event_name'];
        $mapper->produceTopic = $data['produce_topic'];
        $mapper->produceKey = $data['produce_key'];
        $mapper->availableVersions = $data['available_versions'];
        $mapper->attributes = $data['attributes'];

        return $mapper;
    }

    /**
     * @return string
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * @return string
     */
    public function getProduceTopic(): string
    {
        return $this->produceTopic;
    }

    /**
     * @return string
     */
    public function getProduceKey(): string
    {
        return $this->produceKey;
    }

    /**
     * @return array
     */
    public function getAvailableVersions(): array
    {
        return $this->availableVersions;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
