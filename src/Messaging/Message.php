<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Messaging;

use Borobudur\Component\Parameter\ImmutableParameter;
use DateTimeImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Ramsey\Uuid\UuidInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface Message extends Arrayable
{
    public const TYPE_COMMON = 'common';
    public const TYPE_COMMAND = 'command';
    public const TYPE_EVENT = 'event';
    public const TYPE_QUERY = 'query';

    public function messageId();

    public function messageType(): string;

    public function messageName(): string;

    public function messageVersion();

    public function metadata(): ImmutableParameter;

    public function payload(): ImmutableParameter;

    public function createdAt(): DateTimeImmutable;
}
