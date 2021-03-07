<?php

declare(strict_types=1);

namespace Tests;

use Pandawa\Arjuna\Messaging\DomainEvent;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class TestEvent extends DomainEvent
{
    public function __construct(array $payload, array $metadata = [])
    {
        $this->init();

        $this->metadata = $metadata;
        $this->payload = $payload;
    }
}
