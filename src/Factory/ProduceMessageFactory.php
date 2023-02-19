<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Factory;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Pandawa\Arjuna\Broker\ProduceMessage;
use Pandawa\Arjuna\Messaging\Message;
use Pandawa\Arjuna\Stamp\DistributedMessageStamp;
use Pandawa\Component\Bus\Stamp\MessageNameStamp;
use Pandawa\Component\Transformer\DataTransformer;
use Pandawa\Contracts\Bus\Envelope;
use Pandawa\Contracts\Transformer\Context;
use Pandawa\Contracts\Transformer\TransformerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class ProduceMessageFactory implements ProductMessageFactoryInterface
{
    private readonly TransformerInterface $transformer;

    public function __construct(
        private readonly string $defaultTopic,
        private readonly NormalizerInterface $normalizer,
        ?TransformerInterface $transformer = null,
    ) {
        $this->transformer = $transformer ?? new DataTransformer();
    }

    /**
     * Create broker messages from domain message.
     *
     * @param  Envelope  $envelope
     *
     * @return ProduceMessage[]
     * @throws Exception
     */
    public function createFromMessage(Envelope $envelope): array
    {
        if (null === $distributeStamp = $envelope->last(DistributedMessageStamp::class)) {
            return [];
        }

        $versions = null === $distributeStamp->versions
            ? [null]
            : array_keys($distributeStamp->versions);

        return array_map(
            function ($version) use ($envelope, $distributeStamp) {
                return $this->createBrokerMessage($version, $envelope, $distributeStamp);
            },
            $versions
        );
    }

    /**
     * Create message broker and transform the value.
     */
    private function createBrokerMessage(
        string|null $version,
        Envelope $envelope,
        DistributedMessageStamp $distributeStamp
    ): ProduceMessage {
        $name = $this->getEventName($envelope);
        $topic = $this->getProduceTopic($version, $distributeStamp);

        if (null === $key = $this->getProduceKey($envelope, $distributeStamp)) {
            throw new InvalidArgumentException(
                sprintf('Attribute "%s" is not found in class "%s".', $key, get_class($envelope->message))
            );
        }

        return ProduceMessage::fromArray(
            [
                'produce_key'     => $key,
                'produce_topic'   => $topic,
                'message_name'    => $name,
                'message_type'    => Message::TYPE_EVENT,
                'message_version' => $version,
                'payload'         => $this->serialize(
                    $version,
                    $distributeStamp->versions[$version] ?? [],
                    $envelope->message
                ),
            ]
        );
    }

    private function serialize(?string $version, array $allowedAttributes, object $message): array
    {
        if ($message instanceof Arrayable) {
            $data = $message->toArray();
        } else {
            $data = $this->normalizer->normalize($message);
        }

        if (null !== $version && count($allowedAttributes)) {
            return $this->transformer->process(
                new Context(selects: $allowedAttributes),
                $data
            );
        }

        return $data;
    }

    /**
     * Get message value with segmented key support.
     */
    private function getMessageValue(string $key, mixed $message): mixed
    {
        if (!str_contains($key, '.')) {
            return $this->getValue($key, $message);
        }

        $value = $message;

        foreach (explode('.', $key) as $segment) {
            if (null === $value || is_scalar($value)) {
                return null;
            }

            $value = $this->getValue($segment, $value);
        }

        return $value;
    }

    /**
     * Get value from data object.
     */
    private function getValue(string $key, mixed $data): mixed
    {
        if (is_object($data)) {
            if (method_exists($data, $getter = Str::camel($key))) {
                return $data->{$getter}();
            }

            if (method_exists($data, $getter = Str::camel(sprintf('get_%s', $key)))) {
                return $data->{$getter}();
            }

            if (method_exists($data, $getter = Str::camel(sprintf('is_%s', $key)))) {
                return $data->{$getter}();
            }

            return $data->{$key};
        }

        if (is_array($data)) {
            return $data[$key] ?? null;
        }

        return null;
    }

    /**
     * Get event name from domain message.
     */
    private function getEventName(Envelope $envelope): string
    {
        return $envelope->last(MessageNameStamp::class)?->name ?? get_class($envelope->message);
    }

    /**
     * Get message produce key.
     */
    private function getProduceKey(Envelope $envelope, DistributedMessageStamp $distributeStamp): mixed
    {
        if (null !== $produceKey = $distributeStamp->produceKey) {
            return $this->getMessageValue($produceKey, $envelope->message);
        }

        return Str::uuid()->toString();
    }

    /**
     * Get message produce topic.
     */
    private function getProduceTopic(string|null $version, DistributedMessageStamp $distributeStamp): string
    {
        $topic = $distributeStamp->produceTopic ?? $this->defaultTopic;

        return $version ? sprintf('%s.%s', $version, $topic) : $topic;
    }
}
