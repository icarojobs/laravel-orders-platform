<?php

declare(strict_types=1);

namespace NotificationService;

final readonly class Notification
{
    public function __construct(
        public string $eventId,
        public string $type,
        public string $recipient,
        public string $subject,
        public string $body,
    ) {}
}
