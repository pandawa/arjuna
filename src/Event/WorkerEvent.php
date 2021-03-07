<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Event;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class WorkerEvent
{
    /**
     * @var string
     */
    protected $brokerName;

    /**
     * @var array
     */
    protected $topics;

    /**
     * Constructor.
     *
     * @param string $brokerName
     * @param array  $topics
     */
    public function __construct(string $brokerName, array $topics)
    {
        $this->brokerName = $brokerName;
        $this->topics = $topics;
    }

    /**
     * @return string
     */
    public function getBrokerName(): string
    {
        return $this->brokerName;
    }

    /**
     * @return array
     */
    public function getTopics(): array
    {
        return $this->topics;
    }
}
