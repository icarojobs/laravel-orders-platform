<?php

declare(strict_types=1);

namespace NotificationService\Store;

use NotificationService\Notification;

interface NotificationStore
{
    /**
     * Returns false when a notification for the same event was already stored.
     */
    public function add(Notification $notification): bool;
}
