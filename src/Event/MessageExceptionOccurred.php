<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Event;

use Exception;
use Pandawa\Arjuna\Messaging\Message;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class MessageExceptionOccurred extends MessageEvent
{
    public function __construct(
        string $brokerName,
        Message $message,
        public readonly Exception $exception,
        array $topics
    ) {
        parent::__construct($brokerName, $message, $topics);
    }
}
