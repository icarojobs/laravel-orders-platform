<?php

use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function actingAsRole(string $role = 'operator', array $abilities = ['orders:read', 'orders:write']): User
{
    $user = User::factory()->create(['role' => $role]);
    Sanctum::actingAs($user, $abilities);

    return $user;
}

it('lists orders with customer and items, newest first', function () {
    actingAsRole();
    $older = Order::factory()->withItems(2)->create(['placed_at' => now()->subDays(2)]);
    $newer = Order::factory()->withItems(1)->create(['placed_at' => now()]);

    $this->getJson(route('api.v1.orders.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.number', $newer->number)
        ->assertJsonPath('data.1.number', $older->number)
        ->assertJsonCount(2, 'data.1.items')
        ->assertJsonStructure([
            'data' => [['id', 'number', 'status', 'status_label', 'total_cents', 'customer' => ['id', 'name'], 'items' => [['sku', 'product', 'quantity']]]],
            'meta' => ['current_page', 'total'],
        ]);
});

it('filters orders by status, customer, number and period', function () {
    actingAsRole();
    $customer = Customer::factory()->create(['name' => 'Acme Distribuidora']);
    $target = Order::factory()->for($customer)->status(OrderStatus::Paid)->create(['placed_at' => '2026-03-10 10:00:00']);
    Order::factory()->status(OrderStatus::Paid)->create(['placed_at' => '2026-01-10 10:00:00']);
    Order::factory()->for($customer)->create(['placed_at' => '2026-03-11 10:00:00']);

    $this->getJson(route('api.v1.orders.index', ['status' => 'paid', 'from' => '2026-03-01', 'to' => '2026-03-31']))
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $target->id);

    $this->getJson(route('api.v1.orders.index', ['customer_id' => $customer->id]))
        ->assertJsonCount(2, 'data');

    $this->getJson(route('api.v1.orders.index', ['search' => 'acme']))
        ->assertJsonCount(2, 'data');

    $this->getJson(route('api.v1.orders.index', ['search' => $target->number]))
        ->assertJsonCount(1, 'data');
});

it('validates list filters', function () {
    actingAsRole();

    $this->getJson(route('api.v1.orders.index', ['status' => 'lost', 'per_page' => 500]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status', 'per_page']);
});

it('places an order through the api', function () {
    $user = actingAsRole();
    $customer = Customer::factory()->create();
    $product = Product::factory()->create(['price_cents' => 2_500, 'stock' => 10]);

    $this->postJson(route('api.v1.orders.store'), [
        'customer_id' => $customer->id,
        'items' => [['product_id' => $product->id, 'quantity' => 4]],
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.total_cents', 10_000)
        ->assertJsonPath('data.items.0.quantity', 4);

    expect(Order::sole()->user_id)->toBe($user->id)
        ->and($product->fresh()->stock)->toBe(6);
});

it('answers 422 when stock is not enough', function () {
    actingAsRole();
    $product = Product::factory()->create(['stock' => 1]);

    $this->postJson(route('api.v1.orders.store'), [
        'customer_id' => Customer::factory()->create()->id,
        'items' => [['product_id' => $product->id, 'quantity' => 2]],
    ])->assertUnprocessable()->assertJsonPath('message', fn (string $message) => str_contains($message, 'in stock'));
});

it('validates the new order payload', function () {
    actingAsRole();

    $this->postJson(route('api.v1.orders.store'), ['customer_id' => 999, 'items' => [['product_id' => 999, 'quantity' => 0]]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['customer_id', 'items.0.product_id', 'items.0.quantity']);
});

it('shows a single order', function () {
    actingAsRole('viewer', ['orders:read']);
    $order = Order::factory()->withItems(3)->create();

    $this->getJson(route('api.v1.orders.show', $order))
        ->assertOk()
        ->assertJsonPath('data.number', $order->number)
        ->assertJsonCount(3, 'data.items');
});

it('moves an order to the next status', function () {
    actingAsRole();
    $order = Order::factory()->status(OrderStatus::Paid)->create();

    $this->patchJson(route('api.v1.orders.status', $order), ['status' => 'shipped'])
        ->assertOk()
        ->assertJsonPath('data.status', 'shipped');
});

it('answers 422 for invalid transitions', function () {
    actingAsRole();
    $order = Order::factory()->create();

    $this->patchJson(route('api.v1.orders.status', $order), ['status' => 'delivered'])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Order cannot move from [pending] to [delivered].');
});

describe('authorization', function () {
    it('keeps viewers read only', function () {
        actingAsRole('viewer', ['orders:read']);

        $this->postJson(route('api.v1.orders.store'), [])->assertForbidden();
        $this->patchJson(route('api.v1.orders.status', Order::factory()->create()), ['status' => 'paid'])->assertForbidden();
    });

    it('respects token abilities even for operators', function () {
        actingAsRole('operator', ['orders:read']);

        $this->postJson(route('api.v1.orders.store'), [])->assertForbidden();
    });

    it('lets only admins cancel orders', function () {
        $order = Order::factory()->create();

        actingAsRole('operator');
        $this->patchJson(route('api.v1.orders.status', $order), ['status' => 'cancelled'])->assertForbidden();

        actingAsRole('admin');
        $this->patchJson(route('api.v1.orders.status', $order), ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    });

    it('requires authentication', function () {
        $this->getJson(route('api.v1.orders.index'))->assertUnauthorized();
    });
});
