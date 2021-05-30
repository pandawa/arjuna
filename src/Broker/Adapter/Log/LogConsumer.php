<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker\Adapter\Log;

use Pandawa\Arjuna\Broker\ConsumedMessage;
use Pandawa\Arjuna\Broker\Consumer;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class LogConsumer implements Consumer
{
    public function subscribe(array $topics): void
    {
        logger(sprintf('Topics "%s" was subscribed.', implode(',', $topics)));
    }

    public function consume(int $timeout = 1000 * 120): ?ConsumedMessage
    {
        sleep(60 * 60);

        return null;
    }
}
