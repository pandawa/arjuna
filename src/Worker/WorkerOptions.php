<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Worker;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class WorkerOptions
{

    public function __construct(
        public readonly array $topics,
        public readonly int $timeout,
        public readonly ?string $broker,
        public readonly ?string $queue,
        public readonly ?string $queueConnection
    ) {
    }
}
