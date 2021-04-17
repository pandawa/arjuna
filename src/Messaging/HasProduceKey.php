<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Messaging;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface HasProduceKey
{
    public function getProduceKey(): string;
}
