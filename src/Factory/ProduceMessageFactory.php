<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Factory;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Pandawa\Arjuna\Broker\ProduceMessage;
use Pandawa\Arjuna\Mapper\EventMapper;
use Pandawa\Arjuna\Mapper\RegistryMapper;
use Pandawa\Arjuna\Messaging\HasProduceKey;
use Pandawa\Arjuna\Messaging\Message;
use Pandawa\Arjuna\Messaging\SelfProduceMessage;
use Pandawa\Component\Message\AbstractMessage;
use Pandawa\Component\Message\NameableMessageInterface;
use Pandawa\Component\Transformer\TransformerRegistryInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class ProduceMessageFactory
{
    /**
     * @var RegistryMapper
     */
    private $registry;

    /**
     * @var TransformerRegistryInterface
     */
    private $transformer;

    /**
     * Constructor.
     *
     * @param RegistryMapper               $registry
     * @param TransformerRegistryInterface $transformer
     */
    public function __construct(RegistryMapper $registry, TransformerRegistryInterface $transformer)
    {
        $this->registry = $registry;
        $this->transformer = $transformer;
    }

    /**
     * Create broker messages from domain message.
     *
     * @param AbstractMessage $message
     *
     * @return ProduceMessage[]
     * @throws Exception
     */
    public function createFromMessage(AbstractMessage $message): array
    {
        if (!$this->registry->has($eventName = $this->getEventName($message))) {
            if ($message instanceof SelfProduceMessage) {
                return [$this->createBrokerMessage(null, $message, $message)];
            }

            return [];
        }

        $mapper = $this->registry->get($eventName);

        if (empty($mapper->getAvailableVersions())) {
            return [$this->createBrokerMessage(null, $mapper, $message)];
        }

        return array_map(
            function ($version) use ($mapper, $message) {
                return $this->createBrokerMessage($version, $mapper, $message);
            },
            $mapper->getAvailableVersions()
        );
    }

    /**
     * Create message broker and transform the value.
     *
     * @param string|null        $version
     * @param SelfProduceMessage $mapper
     * @param AbstractMessage    $message
     *
     * @return ProduceMessage
     * @throws Exception
     */
    private function createBrokerMessage($version, SelfProduceMessage $mapper, AbstractMessage $message): ProduceMessage
    {
        $name = $message instanceof NameableMessageInterface ? $message::name() : get_class($message);
        $topic = $version ? sprintf('v%s.%s', $version, $mapper->getProduceTopic()) : $mapper->getProduceTopic();

        if (null === $key = $this->getProduceKey($mapper, $message)) {
            throw new InvalidArgumentException(
                sprintf('Attribute "%s" is not found in class "%s".', $key, get_class($message))
            );
        }

        return ProduceMessage::fromArray(
            [
                'produce_key'     => $key,
                'produce_topic'   => $topic,
                'message_name'    => $name,
                'message_type'    => Message::TYPE_EVENT,
                'message_version' => $version,
                'payload'         => $this->transformer->transform($this->transformPayload($version, $mapper, $message)),
            ]
        );
    }

    /**
     * Transform message payload from event mapper and version.
     *
     * @param string|null        $version
     * @param SelfProduceMessage $mapper
     * @param AbstractMessage    $message
     *
     * @return array
     */
    private function transformPayload($version, SelfProduceMessage $mapper, AbstractMessage $message): array
    {
        $data = [];

        if ($mapper instanceof EventMapper) {
            $mapping = $mapper->getAttributes();

            foreach ($mapping as $key => $value) {
                if (is_numeric($key)) {
                    Arr::set($data, $value, $this->getMessageValue($value, $message));

                    continue;
                }

                if (!$this->availableInVersion($version, $key, $value)) {
                    continue;
                }

                Arr::set($data, $key, $this->getMessageValue($value['from'] ?? $key, $message));
            }
        } else {
            foreach ($message->payload()->keys() as $key) {
                Arr::set($data, $key, $this->getMessageValue($key, $message));
            }
        }

        return $data;
    }

    /**
     * Check if the given key is available for given version.
     *
     * @param string|null $version
     * @param string      $key
     * @param             $value
     *
     * @return bool
     */
    private function availableInVersion($version, string $key, $value): bool
    {
        if (null === $version || !is_array($value)) {
            return true;
        }

        if (!array_key_exists('versions', (array)$value)) {
            return true;
        }

        $versions = $value['versions'] ?? [];

        return in_array($version, $versions);
    }

    /**
     * Get message value with segmented key support.
     *
     * @param string          $key
     * @param AbstractMessage $message
     *
     * @return mixed|AbstractMessage|null
     */
    private function getMessageValue(string $key, AbstractMessage $message)
    {
        if (false === strpos($key, '.')) {
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

    private function getValue(string $key, $data)
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

            if ($data instanceof AbstractMessage) {
                return $data->payload()->get($key);
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
     *
     * @param AbstractMessage $message
     *
     * @return string
     */
    private function getEventName(AbstractMessage $message): string
    {
        if ($message instanceof NameableMessageInterface) {
            return $message::name();
        }

        return get_class($message);
    }

    private function getProduceKey($mapper, AbstractMessage $message)
    {
        if ($mapper instanceof HasProduceKey || $mapper instanceof EventMapper) {
            return $this->getMessageValue($mapper->getProduceKey(), $message);
        }

        return Str::uuid()->toString();
    }
}
