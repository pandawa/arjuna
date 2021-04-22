<?php

declare(strict_types=1);

namespace Pandawa\Arjuna\Mapper;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class RegistryMapper
{
    private $mappers = [];

    public function __construct(array $mappers = [])
    {
        foreach ($mappers as $event => $option) {
            if (is_int($event)) {
                $this->add($option, []);

                continue;
            }

            $this->add($event, $option);
        }
    }

    public function has(string $eventName): bool
    {
        return array_key_exists($eventName, $this->mappers);
    }

    public function add(string $eventName, array $mapping): void
    {
        $this->mappers[$eventName] = $mapping;
    }

    public function get(string $eventName): EventMapper
    {
        return EventMapper::createFromArray(array_merge($this->mappers[$eventName], ['event_name' => $eventName]));
    }
}
