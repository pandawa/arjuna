<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Messaging;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface HasProduceTopic
{
    public function getProduceTopic(): string;
}
