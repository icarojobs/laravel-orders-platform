<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    private const ITEMS = ['Cabo HDMI', 'Monitor', 'Teclado', 'Mouse', 'Headset', 'Notebook', 'SSD', 'Roteador', 'Webcam', 'Cadeira'];

    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->bothify('SKU-####-??'),
            'name' => sprintf('%s %s', fake()->randomElement(self::ITEMS), fake()->bothify('?##')),
            'price_cents' => fake()->numberBetween(990, 99_900),
            'stock' => fake()->numberBetween(20, 500),
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(['stock' => 0]);
    }
}
