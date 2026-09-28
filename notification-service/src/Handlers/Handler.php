<?php

declare(strict_types=1);

namespace NotificationService\Handlers;

use NotificationService\Message\OrderEvent;
use NotificationService\Notification;

interface Handler
{
    public function handles(): string;

    public function notificationFor(OrderEvent $event): Notification;
}
