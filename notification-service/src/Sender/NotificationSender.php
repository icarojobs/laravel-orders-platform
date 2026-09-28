<?php

declare(strict_types=1);

namespace NotificationService\Sender;

use NotificationService\Notification;

interface NotificationSender
{
    public function send(Notification $notification): void;
}
