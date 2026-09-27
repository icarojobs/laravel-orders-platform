<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->bothify('SKU-####-??'),
            'name' => fake()->unique()->catchPhrase(),
            'price_cents' => fake()->numberBetween(990, 99_900),
            'stock' => fake()->numberBetween(20, 500),
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(['stock' => 0]);
    }
}
