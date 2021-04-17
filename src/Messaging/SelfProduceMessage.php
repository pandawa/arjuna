<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Messaging;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface SelfProduceMessage extends ProduceMessage
{
    public function getProduceTopic(): string;
}
