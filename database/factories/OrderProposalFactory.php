<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderProposal>
 */
class OrderProposalFactory extends Factory
{
    public function definition(): array
    {
        $total = fake()->numberBetween(100, 5000);

        return [
            'proposal_number' => 'P-'.fake()->unique()->numerify('######'),
            'user_id' => User::factory(),
            'client_name' => fake()->company(),
            'total_cost' => $total,
            'corrections_count' => 0,
            'payload' => $this->payload($total),
            'deleted_by' => null,
            'deleted_date' => null,
            'is_autosaved' => false,
            'autosaved_by' => null,
            'autosaved_at' => null,
            'autosave_token' => null,
            'autosave_confirmed_by' => null,
            'autosave_confirmed_at' => null,
        ];
    }

    public function payload(float|int $total = 100): array
    {
        return [
            'client_id' => null,
            'client_name' => null,
            'urgency_coefficient' => '1.00',
            'urgencyCoefficient' => '1.00',
            'products' => [
                [
                    'index' => 1,
                    'uid' => fake()->uuid(),
                    'isExpanded' => true,
                    'productTypeId' => null,
                    'productTypeName' => 'УФ друк',
                    'material' => 'Тестовий матеріал',
                    'materialType' => 'Листовий',
                    'category' => 'Тест',
                    'thickness' => '3.00',
                    'manualThickness' => null,
                    'positions' => [
                        [
                            'index' => 1,
                            'width' => '1',
                            'height' => '1',
                            'qty' => '1',
                            'cmyk' => '1',
                            'white' => '0',
                            'cost' => (float) $total,
                            'purchase_cost' => 0,
                        ],
                    ],
                    'servicesEnabledRaw' => '0',
                    'services' => [],
                    'service_rows' => [],
                    'positions_cost' => (float) $total,
                    'positions_purchase_cost' => 0,
                    'services_cost' => 0,
                    'services_purchase_cost' => 0,
                    'total_cost' => (float) $total,
                    'calculated_purchase_cost' => 0,
                ],
            ],
            'summary' => [
                'order_total' => (float) $total,
                'calculated_purchase_cost' => 0,
                'urgency_coefficient' => '1.00',
            ],
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
        ]);
    }

    public function deleted(?User $user = null): static
    {
        return $this->state(fn () => [
            'deleted_by' => $user?->id,
            'deleted_date' => now(),
        ]);
    }

    public function withPayload(array $payload): static
    {
        return $this->state(fn () => [
            'payload' => $payload,
            'total_cost' => (float) data_get($payload, 'summary.order_total', 0),
            'client_name' => data_get($payload, 'client_name'),
        ]);
    }
}
