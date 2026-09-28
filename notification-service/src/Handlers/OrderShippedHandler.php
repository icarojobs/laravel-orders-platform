<?php

declare(strict_types=1);

namespace NotificationService\Handlers;

use NotificationService\Message\OrderEvent;
use NotificationService\Notification;

final class OrderShippedHandler implements Handler
{
    public function handles(): string
    {
        return 'order.shipped';
    }

    public function notificationFor(OrderEvent $event): Notification
    {
        return new Notification(
            eventId: $event->eventId,
            type: $this->handles(),
            recipient: $event->customerEmail,
            subject: "Seu pedido {$event->orderNumber} foi enviado",
            body: sprintf(
                "Olá, %s!\n\nO pedido %s saiu para entrega em %s.",
                $event->customerName,
                $event->orderNumber,
                $event->occurredAt->format('d/m/Y H:i'),
            ),
        );
    }
}
