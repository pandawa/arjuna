<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Messaging;

use DateTimeImmutable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface Message extends Arrayable
{
    public const TYPE_COMMON = 'common';
    public const TYPE_COMMAND = 'command';
    public const TYPE_EVENT = 'event';
    public const TYPE_QUERY = 'query';

    public function messageId(): mixed;

    public function messageType(): ?string;

    public function messageName(): ?string;

    public function messageVersion(): int|float|string|null;

    public function metadata(): array;

    public function payload(): array;

    public function createdAt(): ?DateTimeImmutable;
}
