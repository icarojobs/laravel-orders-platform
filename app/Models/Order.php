<?php

namespace App\Models;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Exceptions\InvalidOrderTransition;
use Carbon\CarbonImmutable;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $number
 * @property int $customer_id
 * @property int|null $user_id
 * @property OrderStatus $status
 * @property int $total_cents
 * @property string|null $notes
 * @property CarbonImmutable $placed_at
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $shipped_at
 * @property CarbonImmutable|null $delivered_at
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Customer $customer
 * @property-read User|null $user
 */
#[Fillable(['number', 'customer_id', 'user_id', 'status', 'total_cents', 'notes', 'placed_at'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    private const TIMESTAMP_FOR_STATUS = [
        'paid' => 'paid_at',
        'shipped' => 'shipped_at',
        'delivered' => 'delivered_at',
        'cancelled' => 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total_cents' => 'integer',
            'placed_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
            'shipped_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @param  Builder<Order>  $query
     */
    public function scopeWithStatus(Builder $query, OrderStatus $status): void
    {
        $query->where('status', $status);
    }

    public function transitionTo(OrderStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw InvalidOrderTransition::between($this->status, $target);
        }

        $this->status = $target;

        if ($column = self::TIMESTAMP_FOR_STATUS[$target->value] ?? null) {
            $this->{$column} = now();
        }

        $this->save();
    }

    public function recalculateTotal(): void
    {
        $this->total_cents = (int) $this->items()->sum('total_cents');
    }
}
