<?php

namespace Database\Factories;

use App\Models\PriceItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PriceItemHistory>
 */
class PriceItemHistoryFactory extends Factory
{
    public function definition(): array
    {
        $purchase = fake()->numberBetween(50, 500);
        $service = fake()->numberBetween($purchase, $purchase + 1000);

        return [
            'price_item_id' => PriceItem::factory(),
            'service_price' => $service,
            'purchase_price' => $purchase,
            'markup_percent' => $purchase > 0 ? round((($service - $purchase) / $purchase) * 100, 2) : null,
            'user_id' => User::factory(),
            'created_at' => now(),
        ];
    }
}
