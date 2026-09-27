<?php

namespace App\Domain\Orders\Data;

use InvalidArgumentException;

final readonly class OrderItemData
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }
    }

    /**
     * @param  array{product_id: int|string, quantity: int|string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self((int) $data['product_id'], (int) $data['quantity']);
    }
}
