<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_code' => strtoupper(fake()->unique()->bothify('RNA##')),
            'product_name' => fake()->randomElement([
                'Mens Organic T-shirt',
                'Womens Organic T-shirt',
                'Organic Hoodie',
                'Organic Tote',
            ]),
            'colour' => fake()->safeColorName(),
            'size' => fake()->randomElement(['S', 'M', 'L', 'One']),
            'quantity' => fake()->numberBetween(0, 40),
        ];
    }
}
