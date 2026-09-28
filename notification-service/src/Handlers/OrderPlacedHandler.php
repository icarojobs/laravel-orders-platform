<?php

declare(strict_types=1);

namespace NotificationService\Handlers;

use NotificationService\Message\OrderEvent;
use NotificationService\Notification;

final class OrderPlacedHandler implements Handler
{
    public function handles(): string
    {
        return 'order.placed';
    }

    public function notificationFor(OrderEvent $event): Notification
    {
        return new Notification(
            eventId: $event->eventId,
            type: $this->handles(),
            recipient: $event->customerEmail,
            subject: "Recebemos o seu pedido {$event->orderNumber}",
            body: sprintf(
                "Olá, %s!\n\nSeu pedido %s com %d item(ns) no valor de %s foi registrado e está aguardando pagamento.",
                $event->customerName,
                $event->orderNumber,
                $event->itemsCount,
                Money::brl($event->totalCents),
            ),
        );
    }
}
