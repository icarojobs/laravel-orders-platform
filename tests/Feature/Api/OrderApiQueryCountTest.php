<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

it('lists orders with a constant number of queries', function (int $orders) {
    Sanctum::actingAs(User::factory()->create(), ['orders:read']);
    Order::factory()->count($orders)->withItems(3)->create();

    DB::enableQueryLog();
    $this->getJson(route('api.v1.orders.index', ['per_page' => 50]))->assertOk();

    // count + orders + customers + items + products
    expect(DB::getQueryLog())->toHaveCount(5);
})->with([5, 30]);
