<?php

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Reports\SalesReport;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-31 12:00:00'));

    $keyboard = Product::factory()->create(['sku' => 'KB-01', 'name' => 'Teclado']);
    $mouse = Product::factory()->create(['sku' => 'MS-01', 'name' => 'Mouse']);

    $first = Order::factory()->status(OrderStatus::Paid)->create(['placed_at' => '2026-03-10 09:00:00', 'total_cents' => 30_000]);
    OrderItem::factory()->for($first)->for($keyboard)->create(['quantity' => 2, 'unit_price_cents' => 15_000, 'total_cents' => 30_000]);

    $second = Order::factory()->create(['placed_at' => '2026-03-10 18:00:00', 'total_cents' => 10_000]);
    OrderItem::factory()->for($second)->for($mouse)->create(['quantity' => 2, 'unit_price_cents' => 5_000, 'total_cents' => 10_000]);

    $third = Order::factory()->status(OrderStatus::Shipped)->create(['placed_at' => '2026-03-12 10:00:00', 'total_cents' => 5_000]);
    OrderItem::factory()->for($third)->for($mouse)->create(['quantity' => 1, 'unit_price_cents' => 5_000, 'total_cents' => 5_000]);

    Order::factory()->status(OrderStatus::Cancelled)->create(['placed_at' => '2026-03-12 11:00:00', 'total_cents' => 99_000]);
    Order::factory()->create(['placed_at' => '2026-01-05 10:00:00', 'total_cents' => 1_000]);
});

it('aggregates revenue, statuses, days and top products for a period', function () {
    $report = app(SalesReport::class)->for(CarbonImmutable::parse('2026-03-01'), CarbonImmutable::parse('2026-03-31'));

    expect($report['summary'])->toBe(['orders' => 3, 'revenue_cents' => 45_000, 'average_ticket_cents' => 15_000])
        ->and($report['by_status'])->toBe(['cancelled' => 1, 'paid' => 1, 'pending' => 1, 'shipped' => 1])
        ->and($report['by_day'])->toBe([
            ['day' => '2026-03-10', 'orders' => 2, 'revenue_cents' => 40_000],
            ['day' => '2026-03-12', 'orders' => 1, 'revenue_cents' => 5_000],
        ])
        ->and($report['top_products'])->toBe([
            ['sku' => 'KB-01', 'name' => 'Teclado', 'units' => 2, 'revenue_cents' => 30_000],
            ['sku' => 'MS-01', 'name' => 'Mouse', 'units' => 3, 'revenue_cents' => 15_000],
        ]);
});

it('serves repeated reads from the cache', function () {
    $report = app(SalesReport::class);
    $report->for(now()->subDays(29), now());

    DB::enableQueryLog();
    $report->for(now()->subDays(29), now());

    expect(DB::getQueryLog())->toBeEmpty();
});

it('invalidates the cache when an order changes', function () {
    $report = app(SalesReport::class);
    $before = $report->for(now()->subDays(29), now());

    Order::query()->where('status', OrderStatus::Pending)->where('placed_at', '>', '2026-03-01')->sole()->transitionTo(OrderStatus::Paid);

    $after = $report->for(now()->subDays(29), now());

    expect($after['by_status']['paid'])->toBe($before['by_status']['paid'] + 1);
});

it('exposes the report through the api', function () {
    Sanctum::actingAs(User::factory()->viewer()->create(), ['orders:read']);

    $this->getJson(route('api.v1.reports.sales', ['from' => '2026-03-01', 'to' => '2026-03-31']))
        ->assertOk()
        ->assertJsonPath('data.summary.orders', 3)
        ->assertJsonPath('data.period', ['from' => '2026-03-01', 'to' => '2026-03-31'])
        ->assertJsonCount(2, 'data.top_products');
});

it('validates the period', function () {
    Sanctum::actingAs(User::factory()->create(), ['orders:read']);

    $this->getJson(route('api.v1.reports.sales', ['from' => '2026-03-10', 'to' => '2026-03-01']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('to');
});
