<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Broker;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface Broker
{
    public function send(string $topic, string $key, ProduceMessage $message): void;

    public function consumer(): Consumer;

    public function name(): string;
}
