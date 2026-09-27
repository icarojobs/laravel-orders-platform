<?php

namespace Database\Factories;

use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numerify('ORD-########'),
            'customer_id' => Customer::factory(),
            'status' => OrderStatus::Pending,
            'total_cents' => 0,
            'placed_at' => fake()->dateTimeBetween('-60 days'),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => array_filter([
            'status' => $status,
            'paid_at' => in_array($status, [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered], true) ? now() : null,
            'shipped_at' => in_array($status, [OrderStatus::Shipped, OrderStatus::Delivered], true) ? now() : null,
            'delivered_at' => $status === OrderStatus::Delivered ? now() : null,
            'cancelled_at' => $status === OrderStatus::Cancelled ? now() : null,
        ], fn ($value) => $value !== null));
    }

    public function withItems(int $count = 2): static
    {
        return $this->afterCreating(function (Order $order) use ($count) {
            OrderItem::factory()
                ->count($count)
                ->for($order)
                ->create();

            $order->recalculateTotal();
            $order->save();
        });
    }
}
