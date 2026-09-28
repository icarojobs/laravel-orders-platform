<?php

use App\Models\Order;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects guests to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('shows the sales report of the last 30 days', function () {
    Order::factory()->count(3)->withItems()->create(['placed_at' => now()->subDay()]);
    Order::factory()->withItems()->create(['placed_at' => now()->subDays(45)]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('report.summary.orders', 3)
            ->has('report.by_day', 1)
            ->has('report.top_products')
            ->where('statusLabels.pending', 'Pendente'));
});
