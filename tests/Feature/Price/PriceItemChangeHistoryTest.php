<?php

namespace Tests\Feature\Price;

use App\Models\PriceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesRoles;
use Tests\TestCase;

class PriceItemChangeHistoryTest extends TestCase
{
    use CreatesRoles;
    use RefreshDatabase;

    public function test_new_item_creation_is_logged_with_current_user(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)
            ->post(route('price.store'), [
                'name' => 'Нова тестова послуга',
                'model_type' => 'Послуга',
                'service_price' => 100,
                'purchase_price' => 50,
                'measurement_unit' => 'шт.',
                'comment' => null,
                'for_customer_material' => false,
            ])
            ->assertRedirect(route('price.index'));

        $item = PriceItem::query()->where('name', 'Нова тестова послуга')->firstOrFail();

        $this->assertDatabaseHas('price_item_change_histories', [
            'price_item_id' => $item->id,
            'field' => 'created',
            'old_value' => null,
            'new_value' => 'Нова тестова послуга',
            'user_id' => $user->id,
        ]);
    }

    public function test_editor_can_change_item_name_and_non_price_changes_are_logged(): void
    {
        $user = $this->createUserWithRole([
            'can_price' => true,
            'price_card_access' => true,
            'price_card_edit' => true,
        ]);
        $item = PriceItem::factory()->create([
            'name' => 'Стара назва',
            'comment' => 'Старий коментар',
            'service_price' => 100,
            'purchase_price' => 50,
        ]);

        $response = $this->actingAs($user)
            ->get(route('price.show', $item))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('Історія змін позиції')
            ->assertSee('id="price-item-save-button"', false)
            ->assertSee('price-field-changed', false)
            ->assertSee('Підтвердіть, що внесені зміни у картку товару (поля підсвічені зеленим кольором) мають бути збережені.', false);

        $this->assertMatchesRegularExpression(
            '/<button[^>]*id="price-item-save-button"[^>]*disabled[^>]*>/s',
            $response->getContent()
        );

        $this->actingAs($user)
            ->patch(route('price.update', $item), [
                'name' => 'Нова назва',
                'service_price' => 100,
                'purchase_price' => 50,
                'comment' => 'Новий коментар',
            ])
            ->assertRedirect(route('price.show', $item));

        $this->assertDatabaseHas('price_items', [
            'id' => $item->id,
            'name' => 'Нова назва',
            'comment' => 'Новий коментар',
        ]);
        $this->assertDatabaseHas('price_item_change_histories', [
            'price_item_id' => $item->id,
            'field' => 'name',
            'old_value' => 'Стара назва',
            'new_value' => 'Нова назва',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('price_item_change_histories', [
            'price_item_id' => $item->id,
            'field' => 'comment',
            'old_value' => 'Старий коментар',
            'new_value' => 'Новий коментар',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseCount('price_item_histories', 0);
    }

    public function test_editor_can_change_sheet_thickness_and_change_is_logged(): void
    {
        $user = $this->createUserWithRole([
            'can_price' => true,
            'price_card_access' => true,
            'price_card_edit' => true,
        ]);
        $item = PriceItem::factory()->create([
            'material_type' => 'Листовий',
            'thickness_mm' => 3,
            'service_price' => 100,
            'purchase_price' => 50,
        ]);

        $this->actingAs($user)
            ->get(route('price.show', $item))
            ->assertOk()
            ->assertSee('name="thickness_mm"', false)
            ->assertSee('data-original-value="3"', false);

        $this->actingAs($user)
            ->patch(route('price.update', $item), [
                'name' => $item->name,
                'service_price' => 100,
                'purchase_price' => 50,
                'thickness_mm' => '4.5',
                'comment' => $item->comment,
            ])
            ->assertRedirect(route('price.show', $item));

        $this->assertDatabaseHas('price_items', [
            'id' => $item->id,
            'thickness_mm' => 4.5,
        ]);
        $this->assertDatabaseHas('price_item_change_histories', [
            'price_item_id' => $item->id,
            'field' => 'thickness_mm',
            'old_value' => '3',
            'new_value' => '4.5',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseCount('price_item_histories', 0);
    }

    public function test_sheet_thickness_rejects_more_than_one_decimal_place(): void
    {
        $user = $this->createAdminUser();
        $item = PriceItem::factory()->create([
            'material_type' => 'Листовий',
            'thickness_mm' => 3,
        ]);

        $this->actingAs($user)
            ->from(route('price.show', $item))
            ->patch(route('price.update', $item), [
                'name' => $item->name,
                'service_price' => $item->service_price,
                'purchase_price' => $item->purchase_price,
                'thickness_mm' => '4.55',
                'comment' => $item->comment,
            ])
            ->assertRedirect(route('price.show', $item))
            ->assertSessionHasErrors('thickness_mm');

        $this->assertSame('3.00', $item->refresh()->thickness_mm);
        $this->assertDatabaseMissing('price_item_change_histories', [
            'price_item_id' => $item->id,
            'field' => 'thickness_mm',
        ]);
    }

    public function test_price_changes_stay_out_of_item_change_history(): void
    {
        $user = $this->createAdminUser();
        $item = PriceItem::factory()->create([
            'service_price' => 100,
            'purchase_price' => 50,
        ]);

        $this->actingAs($user)
            ->patch(route('price.update', $item), [
                'name' => $item->name,
                'service_price' => 120,
                'purchase_price' => 60,
                'comment' => $item->comment,
            ])
            ->assertRedirect(route('price.show', $item));

        $this->assertDatabaseCount('price_item_histories', 1);
        $this->assertDatabaseCount('price_item_change_histories', 0);
    }

    public function test_item_name_must_be_unique_among_visible_or_active_items(): void
    {
        $user = $this->createAdminUser();
        $existingItem = PriceItem::factory()->create(['name' => 'Зайнята назва']);
        $item = PriceItem::factory()->create(['name' => 'Поточна назва']);

        $this->actingAs($user)
            ->from(route('price.show', $item))
            ->patch(route('price.update', $item), [
                'name' => $existingItem->name,
                'service_price' => $item->service_price,
                'purchase_price' => $item->purchase_price,
                'comment' => $item->comment,
            ])
            ->assertRedirect(route('price.show', $item))
            ->assertSessionHasErrors('name');

        $this->assertSame('Поточна назва', $item->refresh()->name);
        $this->assertDatabaseCount('price_item_change_histories', 0);
    }

    public function test_status_and_visibility_changes_are_logged_separately_from_prices(): void
    {
        $user = $this->createAdminUser();
        $item = PriceItem::factory()->create([
            'is_active' => true,
            'visible' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('price.toggle', $item))
            ->assertRedirect(route('price.index'));

        $this->actingAs($user)
            ->patch(route('price.hide', $item))
            ->assertRedirect(route('price.index'));

        $this->assertDatabaseHas('price_item_change_histories', [
            'price_item_id' => $item->id,
            'field' => 'is_active',
            'old_value' => '1',
            'new_value' => '0',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('price_item_change_histories', [
            'price_item_id' => $item->id,
            'field' => 'visible',
            'old_value' => '1',
            'new_value' => '0',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseCount('price_item_histories', 0);
    }
}
