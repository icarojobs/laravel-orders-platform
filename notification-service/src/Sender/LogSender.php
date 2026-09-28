<?php

declare(strict_types=1);

namespace NotificationService\Sender;

use NotificationService\Notification;
use Psr\Log\LoggerInterface;

/**
 * Stand-in for an e-mail provider: the notification is already persisted,
 * here it is only written to the service log.
 */
final class LogSender implements NotificationSender
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function send(Notification $notification): void
    {
        $this->logger->info('Notification sent', [
            'type' => $notification->type,
            'to' => $notification->recipient,
            'subject' => $notification->subject,
        ]);
    }
}
