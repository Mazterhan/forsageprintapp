<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\DepartmentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DepartmentPosition>
 */
class DepartmentPositionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'department_category_id' => null,
            'name' => fake()->unique()->jobTitle(),
        ];
    }

    public function inCategory(DepartmentCategory $category): static
    {
        return $this->state(fn () => [
            'department_id' => $category->department_id,
            'department_category_id' => $category->id,
        ]);
    }
}
