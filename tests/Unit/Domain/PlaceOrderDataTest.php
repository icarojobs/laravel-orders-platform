<?php

use App\Domain\Orders\Data\OrderItemData;
use App\Domain\Orders\Data\PlaceOrderData;

it('builds from a validated payload', function () {
    $data = PlaceOrderData::fromArray([
        'customer_id' => '7',
        'items' => [['product_id' => '1', 'quantity' => '2']],
        'notes' => 'Entregar pela manhã',
    ], userId: 3);

    expect($data->customerId)->toBe(7)
        ->and($data->userId)->toBe(3)
        ->and($data->notes)->toBe('Entregar pela manhã')
        ->and($data->items[0])->toEqual(new OrderItemData(1, 2));
});

it('merges repeated products', function () {
    $data = new PlaceOrderData(1, [
        new OrderItemData(10, 2),
        new OrderItemData(11, 1),
        new OrderItemData(10, 3),
    ]);

    expect($data->quantitiesByProduct())->toBe([10 => 5, 11 => 1]);
});

it('requires at least one item', function () {
    new PlaceOrderData(1, []);
})->throws(InvalidArgumentException::class);

it('rejects non positive quantities', function () {
    new OrderItemData(1, 0);
})->throws(InvalidArgumentException::class);
