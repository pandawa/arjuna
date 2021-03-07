<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface Consumer
{
    public function subscribe(array $topics): void;

    public function consume(int $timeout = 1000 * 120): ?ConsumedMessage;
}
