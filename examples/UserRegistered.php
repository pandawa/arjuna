<?php

declare(strict_types=1);

use Pandawa\Component\Message\AbstractMessage;
use Pandawa\Component\Message\NameableMessageInterface;
use Pandawa\Arjuna\Messaging\ProduceMessage;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class UserRegistered extends AbstractMessage implements NameableMessageInterface, ProduceMessage
{
    public static function name(): string
    {
        return 'user-registered';
    }

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
}
