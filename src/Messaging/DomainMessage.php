<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Messaging;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Str;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class DomainMessage implements Message
{
    protected mixed $messageId;

    protected ?string $messageName;

    protected int|float|string|null $messageVersion;

    protected ?string $messageType;

    protected ?DateTimeImmutable $createdAt = null;

    protected array $metadata = [];

    protected array $payload = [];

    public static function fromArray(array $data): static
    {
        $message = new static();
        $message->messageId = $data['message_id'] ?? null;
        $message->messageName = $data['message_name'] ?? null;
        $message->messageVersion = $data['message_version'] ?? null;
        $message->messageType = $data['message_type'] ?? null;
        $message->payload = $data['payload'] ?? [];
        $message->metadata = $data['metadata'] ?? [];

        if (isset($data['created_at'])) {
            $message->createdAt = new DateTimeImmutable(
                $data['created_at']['date'],
                new DateTimeZone($data['created_at']['timezone'])
            );
        }

        $message->init();

        return $message;
    }

    public function messageId(): mixed
    {
        return $this->messageId;
    }

    public function messageName(): string
    {
        return $this->messageName;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function messageVersion(): int|float|string|null
    {
        return $this->messageVersion;
    }

    public function messageType(): ?string
    {
        return $this->messageType;
    }

    public function toArray(): array
    {
        return [
            'message_id'      => $this->messageId(),
            'message_version' => $this->messageVersion(),
            'message_name'    => $this->messageName(),
            'message_type'    => $this->messageType(),
            'metadata'        => $this->metadata,
            'payload'         => $this->payload,
            'created_at'      => [
                'date'     => $this->createdAt()->format('Y-m-d H:i:s'),
                'timezone' => $this->createdAt()->getTimezone()->getName(),
            ],
        ];
    }

    public function withMetadata(array $metadata): Message
    {
        $message = clone $this;

        $message->metadata = $metadata;

        return $message;
    }

    public function withAddedMetadata(string $key, mixed $value): Message
    {
        $message = clone $this;

        $message[$key] = $value;

        return $message;
    }

    public function withVersion(int $version): Message
    {
        $message = clone $this;

        $message->messageVersion = $version;

        return $message;
    }

    protected function init(): void
    {
        if (null === $this->messageId) {
            $this->messageId = (string)Str::uuid();
        }

        if (null === $this->messageName) {
            $this->messageName = get_class($this);
        }

        if (null === $this->messageVersion) {
            $this->messageVersion = 1;
        }

        if (null === $this->messageType) {
            $this->messageType = Message::TYPE_COMMON;
        }

        if (null === $this->createdAt) {
            $this->createdAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        }
    }
}
