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
        if ($event instanceof ConsumedMessage && array_key_exists($event->messageName(), $this->aliases)) {
            $eventClass = $this->aliases[$event->messageName()];
            $event = new $eventClass($event->payload()->all());
        }

        return parent::dispatch($event, $payload, $halt);
    }
}
