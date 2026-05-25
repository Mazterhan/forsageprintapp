<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Department',
            'lead_user_id' => null,
        ];
    }

    public function ledBy(User $user): static
    {
        return $this->state(fn () => [
            'lead_user_id' => $user->id,
        ]);
    }
}
