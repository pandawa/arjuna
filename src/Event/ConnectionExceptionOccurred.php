<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Event;

use Exception;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ConnectionExceptionOccurred
{
    public function __construct(
        public readonly string $brokerName,
        public readonly array $topics,
        public readonly Exception $exception
    ) {
    }
}
