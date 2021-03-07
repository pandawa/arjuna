<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Event;

use Exception;
use Pandawa\Arjuna\Messaging\Message;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class MessageExceptionOccurred extends MessageEvent
{
    /**
     * @var Exception
     */
    private $exception;

    /**
     * Constructor.
     *
     * @param string    $brokerName
     * @param Message   $message
     * @param Exception $exception
     * @param array     $topics
     */
    public function __construct(string $brokerName, Message $message, Exception $exception, array $topics)
    {
        parent::__construct($brokerName, $message, $topics);

        $this->exception = $exception;
    }

    /**
     * @return Exception
     */
    public function getException(): Exception
    {
        return $this->exception;
    }
}
