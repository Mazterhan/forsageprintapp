<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductType>
 */
class ProductTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['УФ друк', 'Чистий матеріал', 'Сольвентний друк']).' '.fake()->unique()->numberBetween(1, 999999),
            'service_internal_code' => null,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function named(string $name): static
    {
        return $this->state(fn () => [
            'name' => $name,
        ]);
    }
}
