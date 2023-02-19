<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Factory;

use Pandawa\Arjuna\Broker\ProduceMessage;
use Pandawa\Contracts\Bus\Envelope;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface ProductMessageFactoryInterface
{
    /**
     * Create broker messages from domain message.
     *
     * @param  Envelope  $envelope
     *
     * @return ProduceMessage[]
     */
    public function createFromMessage(Envelope $envelope): array;
}
