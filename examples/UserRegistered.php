<?php

declare(strict_types=1);

use Pandawa\Component\Message\AbstractMessage;
use Pandawa\Component\Message\NameableMessageInterface;
use Pandawa\Arjuna\Messaging\SelfProduceMessage;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class UserRegistered extends AbstractMessage implements NameableMessageInterface, SelfProduceMessage
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

    public function getProduceKey(): string
    {
        return 'person.id';
    }

    public function getProduceTopic(): string
    {
        return 'order';
    }
}
