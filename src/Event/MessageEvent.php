<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Event;

use Pandawa\Arjuna\Messaging\DomainMessage;
use Pandawa\Arjuna\Messaging\Message;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class MessageEvent
{
    /**
     * @var string
     */
    protected $brokerName;

    /**
     * @var DomainMessage
     */
    protected $message;

    /**
     * @var array
     */
    protected $topics;

    /**
     * Constructor.
     *
     * @param string  $brokerName
     * @param Message $message
     * @param array   $topics
     */
    public function __construct(string $brokerName, Message $message, array $topics)
    {
        $this->brokerName = $brokerName;
        $this->message = $message;
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
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * @return array
     */
    public function getTopics(): array
    {
        return $this->topics;
    }
}
