<?php

namespace App\Domain\Orders\Exceptions;

use App\Models\Product;
use DomainException;

class InsufficientStock extends DomainException
{
    public static function for(Product $product, int $requested): self
    {
        return new self("Product [{$product->sku}] has {$product->stock} unit(s) in stock, {$requested} requested.");
    }
}
