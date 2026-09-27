<?php

namespace App\Domain\Orders\Services;

use App\Domain\Orders\Contracts\OrderNumberGenerator;
use App\Domain\Orders\Data\PlaceOrderData;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Events\OrderPlaced;
use App\Domain\Orders\Exceptions\InsufficientStock;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PlaceOrderService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly OrderNumberGenerator $numbers,
    ) {}

    public function handle(PlaceOrderData $data): Order
    {
        $order = $this->db->transaction(function () use ($data) {
            $quantities = $data->quantitiesByProduct();

            $products = Product::query()
                ->whereKey(array_keys($quantities))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $items = [];
            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId)
                    ?? throw (new ModelNotFoundException)->setModel(Product::class, [$productId]);

                if (! $product->hasStockFor($quantity)) {
                    throw InsufficientStock::for($product, $quantity);
                }

                $product->decrement('stock', $quantity);

                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price_cents' => $product->price_cents,
                    'total_cents' => $product->price_cents * $quantity,
                ];
            }

            $order = Order::create([
                'number' => $this->numbers->next(),
                'customer_id' => $data->customerId,
                'user_id' => $data->userId,
                'status' => OrderStatus::Pending,
                'total_cents' => array_sum(array_column($items, 'total_cents')),
                'notes' => $data->notes,
                'placed_at' => now(),
            ]);

            $order->items()->createMany($items);

            return $order;
        });

        OrderPlaced::dispatch($order);

        return $order->load('items');
    }
}
