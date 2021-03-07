<?php

declare(strict_types=1);

use Pandawa\Arjuna\Messaging\ProduceMessage;
use Pandawa\Component\Message\AbstractMessage;
use Pandawa\Component\Message\NameableMessageInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class UserRegistered extends AbstractMessage implements NameableMessageInterface, ProduceMessage
{
    public function getPerson(): array
    {
        return [
            'id' => 1,
            'name' => 'Iqbal Maulana',
            'age'  => 30,
        ];
    }

    public function getPosition()
    {
        return 'programmer';
    }

    public static function name(): string
    {
        return 'user-registered';
    }
}
