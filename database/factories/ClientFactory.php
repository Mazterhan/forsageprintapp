<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'FP-'.fake()->unique()->numerify('######'),
            'name' => fake()->unique()->company(),
            'type' => 'company',
            'status' => 'active',
            'category' => fake()->randomElement(['Retail', 'Wholesale', null]),
            'is_vip' => false,
            'notes' => null,
            'tags' => null,
            'contact_name' => fake()->name(),
            'phones' => fake()->phoneNumber(),
            'emails' => fake()->safeEmail(),
            'messengers' => null,
            'source' => null,
            'delivery_address' => fake()->address(),
            'delivery_notes' => null,
            'delivery_addresses' => null,
            'manager_id' => null,
            'created_by' => null,
            'updated_by' => null,
            'last_order_at' => null,
        ];
    }

    public function managedBy(User $user): static
    {
        return $this->state(fn () => [
            'manager_id' => $user->id,
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn () => [
            'status' => 'blocked',
        ]);
    }
}
