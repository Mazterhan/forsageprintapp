<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'material_type' => fake()->randomElement(['Листовий', 'Рулонний', 'Без типу матеріалу']),
            'code' => strtoupper(fake()->unique()->bothify('??#')),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function sheet(): static
    {
        return $this->state(fn () => [
            'material_type' => 'Листовий',
        ]);
    }

    public function roll(): static
    {
        return $this->state(fn () => [
            'material_type' => 'Рулонний',
        ]);
    }
}
