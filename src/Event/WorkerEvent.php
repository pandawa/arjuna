<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Event;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class WorkerEvent
{
    public function __construct(
        public readonly string $brokerName,
        public readonly array $topics
    ) {
    }
}
