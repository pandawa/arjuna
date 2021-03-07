<?php

declare(strict_types=1);

namespace Tests;

use Pandawa\Arjuna\Broker\Adapter\Kafka\KafkaBrokerAdapter;
use Pandawa\Arjuna\Broker\BrokerManager;
use PHPUnit\Framework\TestCase;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class KafkaTest extends TestCase
{
    public function testSendMessage(): void
    {
        $broker = $this->broker();
        $event = new TestEvent(['name' => 'Iqbal Maulana']);

        $broker->send('order', $event);

        $broker->consumer()->subscribe(['order']);

        $message = $broker->consumer()->consume();

        $this->assertNotNull($message);
        $this->assertEquals(TestEvent::class, $message->messageName());
        $this->assertEquals($event->messageVersion(), $message->messageVersion());
        $this->assertEquals($event->messageType(), $message->messageType());
        $this->assertEquals('Iqbal Maulana', $message->payload()->get('name'));
        $this->assertEquals($event->messageId(), $message->messageId());
    }

    private function broker(): KafkaBrokerAdapter
    {
        return new KafkaBrokerAdapter(
            'localhost:9092',
            'default',
            'snappy',
            false,
            false
        );
    }
}
