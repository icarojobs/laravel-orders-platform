<?php

namespace Database\Seeders;

use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $total = (int) (getenv('SEED_ORDERS') ?: 500);

        $customers = Customer::factory()->count(50)->create()->modelKeys();
        $products = Product::factory()->count(40)->create()->pluck('price_cents', 'id')->all();
        $statuses = OrderStatus::cases();

        DB::transaction(function () use ($total, $customers, $products, $statuses) {
            foreach (array_chunk(range(1, $total), 500) as $chunk) {
                $orders = [];
                foreach ($chunk as $sequence) {
                    $placedAt = now()->subMinutes(random_int(0, 60 * 24 * 90));
                    $status = Arr::random($statuses);
                    $orders[] = [
                        'number' => sprintf('ORD-%08d', $sequence),
                        'customer_id' => Arr::random($customers),
                        'status' => $status->value,
                        'total_cents' => 0,
                        'placed_at' => $placedAt,
                        'paid_at' => in_array($status, [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered], true) ? $placedAt->addHour() : null,
                        'shipped_at' => in_array($status, [OrderStatus::Shipped, OrderStatus::Delivered], true) ? $placedAt->addDay() : null,
                        'delivered_at' => $status === OrderStatus::Delivered ? $placedAt->addDays(3) : null,
                        'cancelled_at' => $status === OrderStatus::Cancelled ? $placedAt->addHours(2) : null,
                        'created_at' => $placedAt,
                        'updated_at' => $placedAt,
                    ];
                }

                DB::table('orders')->insert($orders);
            }

            DB::table('orders')->orderBy('id')->select('id')->chunkById(1000, function ($rows) use ($products) {
                $items = [];
                foreach ($rows as $row) {
                    foreach ((array) array_rand($products, random_int(1, 4)) as $productId) {
                        $quantity = random_int(1, 5);
                        $items[] = [
                            'order_id' => $row->id,
                            'product_id' => $productId,
                            'quantity' => $quantity,
                            'unit_price_cents' => $products[$productId],
                            'total_cents' => $quantity * $products[$productId],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                DB::table('order_items')->insert($items);
            });

            DB::statement('UPDATE orders SET total_cents = (SELECT COALESCE(SUM(total_cents), 0) FROM order_items WHERE order_items.order_id = orders.id)');
        });
    }
}
