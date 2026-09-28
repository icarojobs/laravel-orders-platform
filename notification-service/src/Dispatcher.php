<?php

declare(strict_types=1);

namespace NotificationService;

use NotificationService\Handlers\Handler;
use NotificationService\Message\InvalidMessage;
use NotificationService\Message\OrderEvent;
use NotificationService\Sender\NotificationSender;
use NotificationService\Store\NotificationStore;
use Psr\Log\LoggerInterface;

final class Dispatcher
{
    /** @var array<string, Handler> */
    private array $handlers = [];

    /**
     * @param  iterable<Handler>  $handlers
     */
    public function __construct(
        iterable $handlers,
        private readonly NotificationStore $store,
        private readonly NotificationSender $sender,
        private readonly LoggerInterface $logger,
    ) {
        foreach ($handlers as $handler) {
            $this->handlers[$handler->handles()] = $handler;
        }
    }

    /**
     * @throws InvalidMessage when the message cannot be processed
     */
    public function dispatch(string $body): DispatchResult
    {
        $event = OrderEvent::fromJson($body);

        $handler = $this->handlers[$event->event] ?? null;
        if ($handler === null) {
            $this->logger->notice('Ignoring event without handler', ['event' => $event->event]);

            return DispatchResult::Ignored;
        }

        $notification = $handler->notificationFor($event);

        if (! $this->store->add($notification)) {
            $this->logger->info('Duplicate event skipped', ['event_id' => $event->eventId]);

            return DispatchResult::Duplicate;
        }

        $this->sender->send($notification);

        return DispatchResult::Sent;
    }
}
