<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $totalCost = fake()->randomFloat(2, 100, 10000);

        return [
            'customer_name' => fake()->company(),
            'last_edited_by' => User::factory(),
            'items' => [
                [
                    'item_id' => (string) Str::uuid(),
                    'nomenclature' => fake()->sentence(),
                    'quantity' => 1,
                    'unit_cost' => (int) $totalCost,
                    'sum' => (int) $totalCost,
                ],
            ],
            'payments_total' => 0,
            'amount_due' => $totalCost,
            'total_cost' => $totalCost,
        ];
    }
}
