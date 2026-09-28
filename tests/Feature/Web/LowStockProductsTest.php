<?php

use App\Livewire\LowStockProducts;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

it('renders the inventory page with the component', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('inventory'))
        ->assertOk()
        ->assertSeeLivewire(LowStockProducts::class);
});

it('lists products under the threshold, lowest stock first', function () {
    Product::factory()->create(['name' => 'Cabo', 'stock' => 3]);
    Product::factory()->create(['name' => 'Mouse', 'stock' => 0]);
    Product::factory()->create(['name' => 'Monitor', 'stock' => 300]);

    Livewire::actingAs(User::factory()->create())
        ->test(LowStockProducts::class)
        ->assertSeeInOrder(['Mouse', 'Cabo'])
        ->assertDontSee('Monitor')
        ->set('threshold', 1000)
        ->assertSee('Monitor')
        ->set('threshold', 0)
        ->assertSee('Mouse')
        ->assertDontSee('Cabo');
});

it('validates the threshold', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(LowStockProducts::class)
        ->set('threshold', -5)
        ->assertHasErrors(['threshold' => 'min']);
});

it('restocks a product', function () {
    $product = Product::factory()->create(['name' => 'Cabo', 'stock' => 2]);

    Livewire::actingAs(User::factory()->create())
        ->test(LowStockProducts::class)
        ->call('restock', $product->id)
        ->assertSet('notice', 'Cabo: estoque atualizado para 12.');

    expect($product->fresh()->stock)->toBe(12);
});

it('does not let viewers restock', function () {
    $product = Product::factory()->create(['stock' => 2]);

    Livewire::actingAs(User::factory()->viewer()->create())
        ->test(LowStockProducts::class)
        ->assertDontSee('+10 unidades')
        ->call('restock', $product->id)
        ->assertForbidden();

    expect($product->fresh()->stock)->toBe(2);
});
