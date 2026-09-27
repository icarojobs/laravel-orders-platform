<?php

namespace App\Http\Requests\Api\Orders;

use App\Domain\Orders\Data\PlaceOrderData;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Order::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'between:1,1000'],
        ];
    }

    public function toData(): PlaceOrderData
    {
        /** @var array{customer_id: int, items: list<array{product_id: int, quantity: int}>, notes?: string|null} $validated */
        $validated = $this->validated();

        return PlaceOrderData::fromArray($validated, $this->user()?->id);
    }
}
