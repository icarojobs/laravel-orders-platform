<?php

namespace App\Http\Requests\Api\Orders;

use App\Domain\Orders\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ];
    }

    public function target(): OrderStatus
    {
        return $this->enum('status', OrderStatus::class) ?? OrderStatus::Pending;
    }
}
