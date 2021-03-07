<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Messaging;

use Borobudur\Component\Parameter\ImmutableParameter;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Illuminate\Support\Str;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class DomainMessage implements Message
{
    /**
     * @var mixed
     */
    protected $messageId;

    /**
     * @var string
     */
    protected $messageName;

    /**
     * @var int|float|string
     */
    protected $messageVersion;

    /**
     * @var string
     */
    protected $messageType;

    /**
     * @var DateTimeImmutable
     */
    protected $createdAt;

    /**
     * @var array
     */
    protected $metadata = [];

    /**
     * @var array
     */
    protected $payload = [];

    /**
     * @param array $data
     *
     * @return DomainMessage|static
     * @throws Exception
     */
    public static function fromArray(array $data): DomainMessage
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

    public function messageId()
    {
        return $this->messageId;
    }

    public function messageName(): string
    {
        return $this->messageName;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function metadata(): ImmutableParameter
    {
        return new ImmutableParameter($this->metadata);
    }

    public function payload(): ImmutableParameter
    {
        return new ImmutableParameter($this->payload);
    }

    public function messageVersion()
    {
        return $this->messageVersion;
    }

    public function messageType(): string
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

    /**
     * @param array $metadata
     *
     * @return static|Message
     */
    public function withMetadata(array $metadata): Message
    {
        $message = clone $this;

        $message->metadata = $metadata;

        return $message;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return static|Message
     */
    public function withAddedMetadata(string $key, $value): Message
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
