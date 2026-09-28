<?php

use App\Domain\Orders\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('requires authentication', function () {
    $this->get(route('orders.index'))->assertRedirect(route('login'));
});

it('lists orders with filters', function () {
    $this->actingAs(User::factory()->create());
    $acme = Customer::factory()->create(['name' => 'Acme Ltda']);
    Order::factory()->for($acme)->status(OrderStatus::Paid)->withItems(2)->create();
    Order::factory()->count(3)->withItems()->create();

    $this->get(route('orders.index', ['search' => 'acme', 'status' => 'paid']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('orders/index')
            ->has('orders.data', 1)
            ->where('orders.data.0.customer.name', 'Acme Ltda')
            ->where('orders.meta.total', 1)
            ->where('filters', ['status' => 'paid', 'search' => 'acme'])
            ->has('statuses', 5));
});

it('shows an order with the actions the user may take', function (string $role, array $expected) {
    $this->actingAs(User::factory()->create(['role' => $role]));
    $order = Order::factory()->withItems(2)->create();

    $this->get(route('orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('orders/show')
            ->where('order.data.number', $order->number)
            ->has('order.data.items', 2)
            ->where('transitions', $expected));
})->with([
    'admin' => ['admin', [['value' => 'paid', 'label' => 'Pago'], ['value' => 'cancelled', 'label' => 'Cancelado']]],
    'operator' => ['operator', [['value' => 'paid', 'label' => 'Pago']]],
    'viewer' => ['viewer', []],
]);

it('updates the status from the detail page', function () {
    $this->actingAs(User::factory()->create());
    $order = Order::factory()->create();

    $this->from(route('orders.show', $order))
        ->patch(route('orders.status', $order), ['status' => 'paid'])
        ->assertRedirect(route('orders.show', $order));

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('forbids viewers from changing orders', function () {
    $this->actingAs(User::factory()->viewer()->create());

    $this->patch(route('orders.status', Order::factory()->create()), ['status' => 'paid'])->assertForbidden();
});

it('flashes an error on invalid transitions', function () {
    $this->actingAs(User::factory()->admin()->create());
    $order = Order::factory()->status(OrderStatus::Delivered)->create();

    $this->from(route('orders.show', $order))
        ->patch(route('orders.status', $order), ['status' => 'paid'])
        ->assertRedirect(route('orders.show', $order));

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered);
});
