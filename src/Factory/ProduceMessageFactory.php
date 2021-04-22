<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Factory;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Pandawa\Arjuna\Broker\ProduceMessage;
use Pandawa\Arjuna\Mapper\EventMapper;
use Pandawa\Arjuna\Mapper\RegistryMapper;
use Pandawa\Arjuna\Messaging\HasProduceKey;
use Pandawa\Arjuna\Messaging\HasProduceTopic;
use Pandawa\Arjuna\Messaging\Message;
use Pandawa\Arjuna\Messaging\ProduceMessage as ProduceMessageContract;
use Pandawa\Component\Message\AbstractMessage;
use Pandawa\Component\Message\NameableMessageInterface;
use Pandawa\Component\Transformer\TransformerRegistryInterface;
use ReflectionException;
use ReflectionObject;
use ReflectionParameter;
use ReflectionProperty;

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
     * @param mixed $message
     *
     * @return ProduceMessage[]
     * @throws Exception
     */
    public function createFromMessage($message): array
    {
        if (!$this->registry->has($eventName = $this->getEventName($message))) {
            if ($message instanceof ProduceMessageContract) {
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
     * @param string|null $version
     * @param mixed       $mapper
     * @param mixed       $message
     *
     * @return ProduceMessage
     * @throws Exception
     */
    private function createBrokerMessage($version, $mapper, $message): ProduceMessage
    {
        $name = $this->getEventName($message);
        $topic = $this->getProduceTopic($version, $mapper);

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
     * @param string|null $version
     * @param mixed       $mapper
     * @param mixed       $message
     *
     * @return array
     */
    private function transformPayload($version, $mapper, $message): array
    {
        if ($mapper instanceof EventMapper && !empty($mapping = $mapper->getAttributes())) {
            $data = [];

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

            return $data;
        }

        return $this->serializeMessage($message);
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
     * @param string $key
     * @param mixed  $message
     *
     * @return mixed|AbstractMessage|null
     */
    private function getMessageValue(string $key, $message)
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

    /**
     * Get value from data object.
     *
     * @param string $key
     * @param        $data
     *
     * @return mixed|null
     */
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
     * @param mixed $message
     *
     * @return string
     */
    private function getEventName($message): string
    {
        if ($message instanceof NameableMessageInterface) {
            return $message::name();
        }

        return get_class($message);
    }

    /**
     * Get message keys.
     *
     * @param mixed $message
     *
     * @return array
     */
    private function getMessageKeys($message): array
    {
        if ($message instanceof AbstractMessage) {
            return $message->payload()->keys();
        }

        $reflection = new ReflectionObject($message);

        return array_values(array_map(
            function (ReflectionParameter $param) {
                return $param->getName();
            },
            $reflection->getConstructor()->getParameters()
        ));
    }

    /**
     * Get message produce key.
     *
     * @param $mapper
     * @param $message
     *
     * @return mixed|string|null
     */
    private function getProduceKey($mapper, $message)
    {
        if (($mapper instanceof HasProduceKey || $mapper instanceof EventMapper)
            && null !== $produceKey = $mapper->getProduceKey()) {
            return $this->getMessageValue($produceKey, $message);
        }

        return Str::uuid()->toString();
    }

    /**
     * Get message produce topic.
     *
     * @param $version
     * @param $mapper
     *
     * @return string
     */
    private function getProduceTopic($version, $mapper): string
    {
        if ($mapper instanceof HasProduceTopic && null !== $productTopic = $mapper->getProduceTopic()) {
            $topic = $productTopic;
        } else {
            $topic = config('arjuna.default_topic');
        }

        return $version ? sprintf('v%s.%s', $version, $topic) : $topic;
    }

    /**
     * Serialize message to array.
     *
     * @param mixed $message
     *
     * @return array
     * @throws ReflectionException
     */
    private function serializeMessage($message): array
    {
        if ($message instanceof Arrayable) {
            return $message->toArray();
        }

        if ($message instanceof AbstractMessage) {
            return $message->payload()->all();
        }

        $serialized = [];
        $reflectionObject = new ReflectionObject($message);

        foreach ($this->getMessageKeys($message) as $key) {
            if (null !== $prop = $reflectionObject->getProperty($key)) {
                $serialized[$key] = $this->serializeValue($this->getPropertyValue($prop, $message));

                continue;
            }

            $serialized[$key] = null;
        }

        return $serialized;
    }

    /**
     * Get object property value.
     *
     * @param ReflectionProperty $property
     * @param object             $object
     *
     * @return mixed
     */
    private function getPropertyValue(ReflectionProperty $property, object $object)
    {
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * Serialize value.
     *
     * @param mixed $value
     *
     * @return array|bool|float|int|mixed|string
     */
    private function serializeValue($value)
    {
        if (is_scalar($value)) {
            return $value;
        }

        if (is_array($value)) {
            $serialized = [];
            foreach ($value as $key => $item) {
                $serialized[$key] = $this->serializeValue($item);
            }

            return $serialized;
        }

        return [
            '__serialize' => serialize($value),
        ];
    }
}
