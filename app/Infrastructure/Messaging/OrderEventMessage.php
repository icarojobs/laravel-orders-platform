<?php

namespace App\Infrastructure\Messaging;

use App\Models\Order;
use Illuminate\Support\Str;

/**
 * Contract shared with the notification-service. Bump "version" on breaking changes.
 */
final class OrderEventMessage
{
    /**
     * @return array<string, mixed>
     */
    public static function from(string $event, Order $order): array
    {
        $order->loadMissing('customer')->loadCount('items');

        return [
            'event_id' => (string) Str::uuid(),
            'event' => $event,
            'version' => 1,
            'occurred_at' => now()->toIso8601String(),
            'order' => [
                'id' => $order->id,
                'number' => $order->number,
                'status' => $order->status->value,
                'total_cents' => $order->total_cents,
                'items_count' => $order->items_count,
                'customer' => [
                    'name' => $order->customer->name,
                    'email' => $order->customer->email,
                ],
            ],
        ];
    }
}
