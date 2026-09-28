<?php

declare(strict_types=1);

namespace NotificationService\Store;

use NotificationService\Notification;

final class InMemoryNotificationStore implements NotificationStore
{
    /** @var array<string, Notification> */
    public array $notifications = [];

    public function add(Notification $notification): bool
    {
        if (isset($this->notifications[$notification->eventId])) {
            return false;
        }

        $this->notifications[$notification->eventId] = $notification;

        return true;
    }
}
