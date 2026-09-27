<?php

use App\Domain\Orders\Enums\OrderStatus;

it('allows the happy path of an order', function (OrderStatus $from, OrderStatus $to) {
    expect($from->canTransitionTo($to))->toBeTrue();
})->with([
    'pending to paid' => [OrderStatus::Pending, OrderStatus::Paid],
    'paid to shipped' => [OrderStatus::Paid, OrderStatus::Shipped],
    'shipped to delivered' => [OrderStatus::Shipped, OrderStatus::Delivered],
    'pending to cancelled' => [OrderStatus::Pending, OrderStatus::Cancelled],
    'paid to cancelled' => [OrderStatus::Paid, OrderStatus::Cancelled],
]);

it('rejects invalid transitions', function (OrderStatus $from, OrderStatus $to) {
    expect($from->canTransitionTo($to))->toBeFalse();
})->with([
    'pending to shipped' => [OrderStatus::Pending, OrderStatus::Shipped],
    'shipped to cancelled' => [OrderStatus::Shipped, OrderStatus::Cancelled],
    'delivered to pending' => [OrderStatus::Delivered, OrderStatus::Pending],
    'cancelled to paid' => [OrderStatus::Cancelled, OrderStatus::Paid],
    'paid to paid' => [OrderStatus::Paid, OrderStatus::Paid],
]);

it('knows which statuses are final', function () {
    expect(OrderStatus::Delivered->isFinal())->toBeTrue()
        ->and(OrderStatus::Cancelled->isFinal())->toBeTrue()
        ->and(OrderStatus::Pending->isFinal())->toBeFalse();
});

it('has a portuguese label for every status', function () {
    expect(array_map(fn (OrderStatus $status) => $status->label(), OrderStatus::cases()))
        ->toBe(['Pendente', 'Pago', 'Enviado', 'Entregue', 'Cancelado']);
});
