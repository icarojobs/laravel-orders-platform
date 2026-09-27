<?php

namespace App\Domain\Orders\Queries;

use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class OrderSearch
{
    public function __construct(
        public ?OrderStatus $status = null,
        public ?int $customerId = null,
        public ?string $term = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function fromArray(array $filters): self
    {
        $date = fn (string $key) => filled($filters[$key] ?? null) ? CarbonImmutable::parse((string) $filters[$key]) : null;

        return new self(
            status: OrderStatus::tryFrom((string) ($filters['status'] ?? '')),
            customerId: filled($filters['customer_id'] ?? null) ? (int) $filters['customer_id'] : null,
            term: filled($filters['search'] ?? null) ? trim((string) $filters['search']) : null,
            from: $date('from')?->startOfDay(),
            to: $date('to')?->endOfDay(),
        );
    }

    /**
     * @return Builder<Order>
     */
    public function query(): Builder
    {
        return Order::query()
            ->with(['customer', 'items.product'])
            ->when($this->status, fn (Builder $query, OrderStatus $status) => $query->where('status', $status))
            ->when($this->customerId, fn (Builder $query, int $id) => $query->where('customer_id', $id))
            ->when($this->term, fn (Builder $query, string $term) => $query->where(function (Builder $query) use ($term) {
                $query->whereLike('number', "%{$term}%")
                    ->orWhereHas('customer', fn (Builder $customer) => $customer->whereLike('name', "%{$term}%"));
            }))
            ->when($this->from, fn (Builder $query, CarbonImmutable $from) => $query->where('placed_at', '>=', $from))
            ->when($this->to, fn (Builder $query, CarbonImmutable $to) => $query->where('placed_at', '<=', $to))
            ->latest('placed_at')
            ->latest('id');
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage)->withQueryString();
    }
}
