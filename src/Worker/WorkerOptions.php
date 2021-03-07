<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Worker;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class WorkerOptions
{
    /**
     * Listen message for specific topics.
     *
     * @var array
     */
    private $topics;

    /**
     * Timeout when consuming message
     *
     * @var int
     */
    private $timeout;

    /**
     * @var string|null
     */
    private $broker;

    /**
     * Process message in specific queue
     *
     * @var string|null
     */
    private $queue;

    /**
     * Process message in specific queue connection
     *
     * @var string|null
     */
    private $queueConnection;

    /**
     * Constructor.
     *
     * @param array       $topics
     * @param int         $timeout
     * @param string|null $broker
     * @param string|null $queue
     * @param string|null $queueConnection
     */
    public function __construct(array $topics, int $timeout, ?string $broker, ?string $queue, ?string $queueConnection)
    {
        $this->topics = $topics;
        $this->timeout = $timeout;
        $this->broker = $broker;
        $this->queue = $queue;
        $this->queueConnection = $queueConnection;
    }

    public function getTopics(): array
    {
        return $this->topics;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getBroker(): ?string
    {
        return $this->broker;
    }

    public function getQueue(): ?string
    {
        return $this->queue;
    }

    public function getQueueConnection(): ?string
    {
        return $this->queueConnection;
    }
}
