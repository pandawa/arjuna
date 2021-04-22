<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Dispatcher;

use Illuminate\Events\Dispatcher;
use Pandawa\Arjuna\Broker\ConsumedMessage;
use Pandawa\Component\Message\AbstractMessage;
use Pandawa\Component\Message\NameableMessageInterface;
use ReflectionClass;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class EventDispatcher extends Dispatcher
{
    /**
     * @var array
     */
    private $aliases = [];

    public function listen($events, $listener = null)
    {
        foreach ((array) $events as $event) {
            if (class_exists($event, true)) {
                $reflection = new ReflectionClass($event);
                if ($reflection->implementsInterface(NameableMessageInterface::class) &&
                    $reflection->isSubclassOf(AbstractMessage::class)) {
                    $this->aliases[$event::name()] = $event;
                }
            }
        }

        parent::listen($events, $listener);
    }

    public function dispatch($event, $payload = [], $halt = false)
    {
        if ($event instanceof ConsumedMessage) {
            $eventClass = $this->aliases[$event->messageName()] ?? $event->messageName();
            if (class_exists($eventClass)) {
                $event = $this->deserializeEvent($event, $eventClass);
            }
        }

        return parent::dispatch($event, $payload, $halt);
    }

    private function deserializeEvent(ConsumedMessage $event, string $eventClass)
    {
        $reflectionClass = new ReflectionClass($eventClass);
        $payload = $event->payload()->all();

        if ($reflectionClass->isSubclassOf(AbstractMessage::class)) {
            return new $eventClass($payload);
        }

        $arguments = [];

        foreach ($reflectionClass->getConstructor()->getParameters() as $parameter) {
            $value = $payload[$parameter->getName()] ?? null;

            if (is_array($value) && isset($value['__serialize'])) {
                $arguments[$parameter->getName()] = unserialize($value['__serialize']);

                continue;
            }

            $arguments[$parameter->getName()] = $value;
        }

        return $reflectionClass->newInstanceArgs($arguments);
    }
}
