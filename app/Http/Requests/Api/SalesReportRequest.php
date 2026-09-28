<?php

namespace App\Http\Requests\Api;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class SalesReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan('orders:read') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    public function from(): CarbonImmutable
    {
        return $this->filled('from') ? CarbonImmutable::parse($this->string('from')->value()) : now()->subDays(29);
    }

    public function to(): CarbonImmutable
    {
        return $this->filled('to') ? CarbonImmutable::parse($this->string('to')->value()) : now();
    }
}
