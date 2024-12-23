<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'product_name' => fake()->word(),
            'category_id' => fake()->randomElement([1, 2, 3, 4, 5]),
            'product_store' => fake()->randomNumber(3),
            'buying_price' => fake()->randomNumber(2),
            'selling_price' => fake()->randomNumber(2),
        ];
    }
}
