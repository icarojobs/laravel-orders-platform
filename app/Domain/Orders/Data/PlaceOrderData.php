<?php

namespace App\Domain\Orders\Data;

use InvalidArgumentException;

final readonly class PlaceOrderData
{
    /**
     * @param  list<OrderItemData>  $items
     */
    public function __construct(
        public int $customerId,
        public array $items,
        public ?int $userId = null,
        public ?string $notes = null,
    ) {
        if ($items === []) {
            throw new InvalidArgumentException('An order needs at least one item.');
        }
    }

    /**
     * @param  array{customer_id: int|string, items: list<array{product_id: int|string, quantity: int|string}>, notes?: string|null}  $data
     */
    public static function fromArray(array $data, ?int $userId = null): self
    {
        return new self(
            customerId: (int) $data['customer_id'],
            items: array_map(OrderItemData::fromArray(...), $data['items']),
            userId: $userId,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Quantities grouped by product, so repeated lines are merged.
     *
     * @return array<int, int>
     */
    public function quantitiesByProduct(): array
    {
        $quantities = [];
        foreach ($this->items as $item) {
            $quantities[$item->productId] = ($quantities[$item->productId] ?? 0) + $item->quantity;
        }

        return $quantities;
    }
}
