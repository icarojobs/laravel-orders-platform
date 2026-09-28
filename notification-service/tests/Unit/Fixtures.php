<?php

declare(strict_types=1);

namespace NotificationService\Tests\Unit;

final class Fixtures
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function payload(array $overrides = []): string
    {
        return json_encode(array_replace_recursive([
            'event_id' => '2d7c9f3e-0a41-4c55-9b0e-6f1f7c0b8a11',
            'event' => 'order.placed',
            'version' => 1,
            'occurred_at' => '2026-03-10T14:30:00+00:00',
            'order' => [
                'id' => 42,
                'number' => 'ORD-260310-ABC123',
                'status' => 'pending',
                'total_cents' => 123_456,
                'items_count' => 3,
                'customer' => ['name' => 'Maria Souza', 'email' => 'maria@example.com'],
            ],
        ], $overrides), JSON_THROW_ON_ERROR);
    }
}
