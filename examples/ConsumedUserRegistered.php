<?php

declare(strict_types=1);

use Pandawa\Component\Message\AbstractMessage;
use Pandawa\Component\Message\NameableMessageInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ConsumedUserRegistered extends AbstractMessage implements NameableMessageInterface
{
    protected $position;
    protected $person;

    public static function name(): string
    {
        return 'user-registered';
    }

    /**
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return mixed
     */
    public function getPerson()
    {
        return $this->person;
    }
}
