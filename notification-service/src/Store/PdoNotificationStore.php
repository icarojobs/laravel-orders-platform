<?php

declare(strict_types=1);

namespace NotificationService\Store;

use NotificationService\Notification;
use PDO;

final class PdoNotificationStore implements NotificationStore
{
    public function __construct(private readonly PDO $pdo) {}

    public function migrate(): void
    {
        $this->pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS notifications (
                id BIGSERIAL PRIMARY KEY,
                event_id UUID NOT NULL UNIQUE,
                type VARCHAR(50) NOT NULL,
                recipient VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                body TEXT NOT NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            )
            SQL);
    }

    public function add(Notification $notification): bool
    {
        $statement = $this->pdo->prepare(<<<'SQL'
            INSERT INTO notifications (event_id, type, recipient, subject, body)
            VALUES (:event_id, :type, :recipient, :subject, :body)
            ON CONFLICT (event_id) DO NOTHING
            SQL);

        $statement->execute([
            'event_id' => $notification->eventId,
            'type' => $notification->type,
            'recipient' => $notification->recipient,
            'subject' => $notification->subject,
            'body' => $notification->body,
        ]);

        return $statement->rowCount() === 1;
    }
}
