<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->product->sku,
            'product' => $this->product->name,
            'quantity' => $this->quantity,
            'unit_price_cents' => $this->unit_price_cents,
            'total_cents' => $this->total_cents,
        ];
    }
}
