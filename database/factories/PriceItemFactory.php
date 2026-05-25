<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PriceItem>
 */
class PriceItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'internal_code' => 'MAT-TST-'.fake()->unique()->numerify('###'),
            'name' => fake()->unique()->words(3, true),
            'model_type' => 'Матеріал',
            'category' => 'Тест',
            'material_type' => 'Листовий',
            'internal_name' => 'Тест',
            'service_price' => fake()->numberBetween(100, 1000),
            'purchase_price' => fake()->numberBetween(50, 500),
            'measurement_unit' => 'м2',
            'comment' => null,
            'for_customer_material' => false,
            'width_m' => null,
            'length_m' => null,
            'thickness_mm' => 3,
            'is_active' => true,
            'visible' => true,
        ];
    }

    public function material(string $category = 'Тест', string $materialType = 'Листовий', string $codePrefix = 'MAT-TST-'): static
    {
        return $this->state(fn () => [
            'internal_code' => $codePrefix.fake()->unique()->numerify('###'),
            'model_type' => 'Матеріал',
            'category' => $category,
            'material_type' => $materialType,
            'internal_name' => $category,
            'measurement_unit' => 'м2',
            'for_customer_material' => false,
        ]);
    }

    public function service(?string $internalCode = null): static
    {
        return $this->state(fn () => [
            'internal_code' => $internalCode ?? 'SERV-'.fake()->unique()->numerify('###'),
            'model_type' => 'Послуга',
            'category' => 'Послуга',
            'material_type' => null,
            'internal_name' => null,
            'measurement_unit' => 'м2',
            'for_customer_material' => false,
            'width_m' => null,
            'length_m' => null,
            'thickness_mm' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => [
            'visible' => false,
        ]);
    }
}
