<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Event;

use Exception;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class ConnectionExceptionOccurred
{
    private $brokerName;
    private $exception;
    private $topics;

    /**
     * Constructor.
     *
     * @param string    $brokerName
     * @param array     $topics
     * @param Exception $exception
     */
    public function __construct(string $brokerName, array $topics, Exception $exception)
    {
        $this->brokerName = $brokerName;
        $this->topics = $topics;
        $this->exception = $exception;
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

    /**
     * @return Exception
     */
    public function getException(): Exception
    {
        return $this->exception;
    }
}
