<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Department;
use App\Models\OrderProposal;
use App\Models\PriceItem;
use App\Models\PriceItemHistory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicIdInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_factories_create_public_ids_for_url_bound_models(): void
    {
        foreach ($this->createUrlBoundModels() as $model) {
            $this->assertNotEmpty($model->public_id, $model::class.' public_id is empty.');
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                (string) $model->public_id,
                $model::class.' public_id is not a valid UUID.'
            );
        }
    }

    public function test_public_ids_are_unique_per_model(): void
    {
        $pairs = [
            [OrderProposal::factory()->create(), OrderProposal::factory()->create()],
            [Client::factory()->create(), Client::factory()->create()],
            [User::factory()->create(), User::factory()->create()],
            [Role::factory()->create(), Role::factory()->create()],
            [Department::factory()->create(), Department::factory()->create()],
            [PriceItem::factory()->create(), PriceItem::factory()->create()],
            [PriceItemHistory::factory()->create(), PriceItemHistory::factory()->create()],
        ];

        foreach ($pairs as [$first, $second]) {
            $this->assertNotSame($first->public_id, $second->public_id, $first::class.' public_id is not unique.');
        }
    }

    public function test_public_id_is_not_changed_by_model_update(): void
    {
        foreach ($this->createUrlBoundModels() as $model) {
            $originalPublicId = (string) $model->public_id;

            $model->public_id = '11111111-1111-4111-8111-111111111111';
            $model->save();

            $this->assertSame(
                $originalPublicId,
                (string) $model->refresh()->public_id,
                $model::class.' public_id was changed after update.'
            );
        }
    }

    public function test_translated_models_use_public_id_route_keys(): void
    {
        $this->assertSame('public_id', (new OrderProposal())->getRouteKeyName());
        $this->assertSame('public_id', (new Client())->getRouteKeyName());
        $this->assertSame('public_id', (new User())->getRouteKeyName());
        $this->assertSame('public_id', (new Role())->getRouteKeyName());
        $this->assertSame('public_id', (new Department())->getRouteKeyName());
        $this->assertSame('public_id', (new PriceItem())->getRouteKeyName());
        $this->assertSame('public_id', (new PriceItemHistory())->getRouteKeyName());
    }

    /**
     * @return array<int, Model>
     */
    private function createUrlBoundModels(): array
    {
        return [
            OrderProposal::factory()->create(),
            Client::factory()->create(),
            User::factory()->create(),
            Role::factory()->create(),
            Department::factory()->create(),
            PriceItem::factory()->create(),
            PriceItemHistory::factory()->create(),
        ];
    }
}
