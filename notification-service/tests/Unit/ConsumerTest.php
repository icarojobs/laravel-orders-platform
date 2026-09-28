<?php

declare(strict_types=1);

namespace NotificationService\Tests\Unit;

use NotificationService\Config;
use NotificationService\Dispatcher;
use NotificationService\Handlers\OrderPlacedHandler;
use NotificationService\Messaging\Consumer;
use NotificationService\Notification;
use NotificationService\Sender\NotificationSender;
use NotificationService\Store\InMemoryNotificationStore;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

final class ConsumerTest extends TestCase
{
    private AMQPChannel&MockObject $channel;

    protected function setUp(): void
    {
        $this->channel = $this->createMock(AMQPChannel::class);
    }

    public function test_it_acks_processed_messages(): void
    {
        $this->channel->expects(self::once())->method('basic_ack')->with(7);

        $this->consumer()->handle($this->message(Fixtures::payload()));
    }

    public function test_it_dead_letters_invalid_messages(): void
    {
        $this->channel->expects(self::once())->method('basic_reject')->with(7, false);

        $this->consumer()->handle($this->message('{broken'));
    }

    public function test_it_requeues_once_on_unexpected_failures(): void
    {
        $this->channel->expects(self::once())->method('basic_nack')->with(7, false, true);

        $this->consumer(failingSender: true)->handle($this->message(Fixtures::payload()));
    }

    public function test_it_gives_up_on_redelivered_failures(): void
    {
        $this->channel->expects(self::once())->method('basic_nack')->with(7, false, false);

        $this->consumer(failingSender: true)->handle($this->message(Fixtures::payload(), redelivered: true));
    }

    public function test_it_declares_queue_bindings_and_dead_letter_queue(): void
    {
        $bindings = [];
        $this->channel->expects(self::exactly(3))->method('queue_bind')->willReturnCallback(function (string $queue, string $exchange, string $key = '') use (&$bindings) {
            $bindings[] = [$queue, $exchange, $key];

            return null;
        });

        $this->consumer()->declareTopology();

        self::assertSame([
            ['notifications.order-events.dlq', 'orders.events.dlx', ''],
            ['notifications.order-events', 'orders.events', 'order.placed'],
            ['notifications.order-events', 'orders.events', 'order.shipped'],
        ], $bindings);
    }

    private function consumer(bool $failingSender = false): Consumer
    {
        $sender = new class($failingSender) implements NotificationSender
        {
            public function __construct(private bool $fail) {}

            public function send(Notification $notification): void
            {
                if ($this->fail) {
                    throw new RuntimeException('SMTP down');
                }
            }
        };

        $dispatcher = new Dispatcher([new OrderPlacedHandler], new InMemoryNotificationStore, $sender, new NullLogger);

        return new Consumer($this->channel, $dispatcher, Config::fromEnv([]), new NullLogger);
    }

    private function message(string $body, bool $redelivered = false): AMQPMessage
    {
        $message = new AMQPMessage($body);
        $message->setChannel($this->channel);
        $message->setDeliveryInfo(7, $redelivered, 'orders.events', 'order.placed');

        return $message;
    }
}
