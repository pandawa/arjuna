<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Event;

use Pandawa\Arjuna\Messaging\Message;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class MessageEvent
{
    public function __construct(
        public readonly string $brokerName,
        public readonly Message $message,
        public readonly array $topics
    ) {
    }
}
