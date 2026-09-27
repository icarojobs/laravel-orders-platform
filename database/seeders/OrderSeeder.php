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
    private const CHUNK = 1000;

    public function run(): void
    {
        $total = (int) (getenv('SEED_ORDERS') ?: 500);

        $customers = Customer::factory()->count(50)->create()->modelKeys();
        $products = Product::factory()->count(40)->create()->pluck('price_cents', 'id')->all();

        foreach (array_chunk(range(1, $total), self::CHUNK) as $sequences) {
            DB::transaction(fn () => $this->seedChunk($sequences, $customers, $products));
        }
    }

    /**
     * @param  list<int>  $sequences
     * @param  array<int, int|string>  $customers
     * @param  array<int, int>  $products
     */
    private function seedChunk(array $sequences, array $customers, array $products): void
    {
        $orders = [];
        $itemsByNumber = [];

        foreach ($sequences as $sequence) {
            $number = sprintf('ORD-%08d', $sequence);
            $placedAt = now()->subMinutes(random_int(0, 60 * 24 * 90));
            $status = Arr::random(OrderStatus::cases());

            $items = [];
            foreach ((array) array_rand($products, random_int(1, 4)) as $productId) {
                $quantity = random_int(1, 5);
                $items[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price_cents' => $products[$productId],
                    'total_cents' => $quantity * $products[$productId],
                    'created_at' => $placedAt,
                    'updated_at' => $placedAt,
                ];
            }

            $itemsByNumber[$number] = $items;
            $orders[] = [
                'number' => $number,
                'customer_id' => Arr::random($customers),
                'status' => $status->value,
                'total_cents' => array_sum(array_column($items, 'total_cents')),
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

        $ids = DB::table('orders')->whereIn('number', array_keys($itemsByNumber))->pluck('id', 'number');

        $rows = [];
        foreach ($itemsByNumber as $number => $items) {
            foreach ($items as $item) {
                $rows[] = ['order_id' => $ids[$number]] + $item;
            }
        }

        foreach (array_chunk($rows, self::CHUNK) as $batch) {
            DB::table('order_items')->insert($batch);
        }
    }
}
