<?php

namespace Tests\Feature\Orders;

use App\Models\Client;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesRoles;
use Tests\TestCase;

class OrderEditLockTest extends TestCase
{
    use CreatesRoles;
    use RefreshDatabase;

    public function test_editor_blocks_other_tabs_and_status_changes_and_shows_owner(): void
    {
        $owner = $this->createAdminUser(['name' => 'Редактор']);
        $other = $this->createAdminUser();
        $order = Order::factory()->create();
        $this->actingAs($owner)->get(route('orders.edit', $order))->assertOk();
        $token = $order->activeEditLock()->firstOrFail()->lock_token;
        $this->get(route('orders.edit', $order))->assertRedirect(route('orders.show', $order));
        $this->actingAs($other)->get(route('orders.edit', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('status', \App\Services\OrderEditLockService::MESSAGE);
        $this->get(route('orders.show', $order))->assertOk()
            ->assertSee('border-green-200 bg-green-50', false)
            ->assertSee(\App\Services\OrderEditLockService::MESSAGE);
        $this->patchJson(route('orders.status.update', $order), ['status' => 'cancelled'])->assertStatus(409);
        $this->getJson(route('orders.edit-lock.status', $order))->assertJsonPath('locked', true)->assertJsonPath('user', 'Редактор');
        $this->get(route('orders.show', $order))->assertOk()->assertSee('Замовлення знаходиться на редагуванні');
        $this->get(route('orders.index'))->assertOk()->assertSee('Редагується');
        $this->deleteJson(route('orders.edit-lock.release', $order), ['edit_lock_token' => $token])->assertOk();
        $this->assertDatabaseCount('order_edit_locks', 1);
        $this->actingAs($owner)->deleteJson(route('orders.edit-lock.release', $order), ['edit_lock_token' => $token])->assertOk();
        $this->assertDatabaseCount('order_edit_locks', 0);
    }

    public function test_refresh_rotates_token_old_unload_cannot_release_and_expired_editor_cannot_save(): void
    {
        $owner = $this->createAdminUser();
        $order = Order::factory()->create();
        $this->actingAs($owner)->get(route('orders.edit', $order))->assertOk();
        $oldToken = $order->activeEditLock()->firstOrFail()->lock_token;
        $this->get(route('orders.edit', ['order' => $order, 'edit_token' => $oldToken]))->assertOk();
        $token = $order->activeEditLock()->firstOrFail()->lock_token;
        $this->assertNotSame($oldToken, $token);
        $this->deleteJson(route('orders.edit-lock.release', $order), ['edit_lock_token' => $oldToken])->assertOk();
        $this->patchJson(route('orders.edit-lock.heartbeat', $order), ['edit_lock_token' => $token])->assertOk();
        $this->travel(5)->minutes();
        $this->patchJson(route('orders.edit-lock.heartbeat', $order), ['edit_lock_token' => $token])->assertStatus(409);
        $this->getJson(route('orders.edit-lock.status', $order))->assertJsonPath('locked', false);
        $this->get(route('orders.edit', $order))->assertOk();
        $newToken = $order->activeEditLock()->firstOrFail()->lock_token;
        $data = ['customer_name' => 'Замовник', 'items' => [['nomenclature' => 'Виріб', 'quantity' => 1, 'unit_cost' => 100]]];
        $this->patchJson(route('orders.update', $order), [...$data, 'edit_lock_token' => $token])->assertStatus(409);
        $this->patchJson(route('orders.update', $order), [...$data, 'edit_lock_token' => $newToken])->assertOk();
        $this->assertDatabaseCount('order_edit_locks', 0);
        $this->assertSame('100.00', $order->fresh()->total_cost);
    }

    public function test_client_payment_and_overpayment_batch_are_blocked_without_changing_balances(): void
    {
        $user = $this->createAdminUser();
        $client = Client::factory()->create();
        $order = Order::factory()->create(['client_id' => $client->id, 'total_cost' => 100, 'status' => 'new']);
        $this->actingAs($user)->get(route('orders.edit', $order))->assertOk();
        $data = ['amount' => 50, 'currency' => 'UAH', 'payment_date' => now('Europe/Kiev')->toDateString(), 'payment_time' => '12:00', 'payment_type' => 'order', 'order_public_id' => $order->public_id];
        $this->postJson(route('orders.clients.payments.store', $client), $data)->assertStatus(409);
        $this->postJson(route('orders.clients.payments.store', $client), [...$data, 'payment_source' => 'overpayment', 'order_public_ids' => [$order->public_id]])->assertStatus(409);
        $this->getJson(route('orders.clients.payments.orders', $client))->assertJsonCount(0, 'orders');
        $this->assertDatabaseCount('client_payments', 0);
        $this->assertSame('100.00', $order->fresh()->total_cost);
    }

    public function test_editing_an_existing_client_payment_is_blocked(): void
    {
        $user = $this->createAdminUser();
        $client = Client::factory()->create();
        $order = Order::factory()->create(['client_id' => $client->id, 'total_cost' => 100]);
        $payment = \App\Models\ClientPayment::query()->create([
            'client_id' => $client->id, 'order_id' => $order->id,
            'amount' => 20, 'amount_uah' => 20, 'currency' => 'UAH',
            'payment_type' => 'order', 'paid_at' => now(), 'created_by' => $user->id, 'updated_by' => $user->id,
        ]);
        $this->actingAs($user)->get(route('orders.edit', $order))->assertOk();
        $this->patchJson(route('orders.clients.payments.update', [$client, $payment]), [
            'amount' => 30, 'currency' => 'UAH', 'payment_date' => now('Europe/Kiev')->toDateString(),
            'payment_time' => '12:00', 'payment_type' => 'order', 'order_public_id' => $order->public_id,
        ])->assertStatus(409);
        $this->assertSame(20, $payment->fresh()->amount);
        $this->assertDatabaseCount('client_payment_histories', 0);
    }

    public function test_validation_failure_keeps_lock_and_users_without_edit_permission_cannot_heartbeat(): void
    {
        $owner = $this->createAdminUser();
        $order = Order::factory()->create();
        $this->actingAs($owner)->get(route('orders.edit', $order))->assertOk();
        $token = $order->activeEditLock()->firstOrFail()->lock_token;
        $this->patchJson(route('orders.update', $order), ['edit_lock_token' => $token, 'items' => []])->assertUnprocessable();
        $this->assertDatabaseCount('order_edit_locks', 1);
        $viewer = $this->createUserWithRole(['can_orders' => true, 'orders_access' => true, 'orders_update' => false]);
        $this->actingAs($viewer)->get(route('orders.edit', $order))->assertForbidden();
        $this->patchJson(route('orders.edit-lock.heartbeat', $order), ['edit_lock_token' => $token])->assertForbidden();
        $this->assertDatabaseCount('order_edit_locks', 1);
    }

    public function test_merge_excludes_locked_orders_and_rejects_both_directions_for_all_users(): void
    {
        $owner = $this->createAdminUser();
        $other = $this->createAdminUser();
        $client = Client::factory()->create();
        $editingOrder = Order::factory()->create(['client_id' => $client->id, 'status' => 'new', 'payments_total' => 0]);
        $availableOrder = Order::factory()->create(['client_id' => $client->id, 'status' => 'new', 'payments_total' => 0]);
        $originalItems = $editingOrder->items;
        $originalCost = $editingOrder->total_cost;
        $this->actingAs($owner)->get(route('orders.edit', $editingOrder))->assertOk();
        $token = $editingOrder->activeEditLock()->firstOrFail()->lock_token;

        foreach ([$owner, $other] as $user) {
            $this->actingAs($user)->getJson(route('orders.merge-candidates', $editingOrder))->assertStatus(409);
            $this->getJson(route('orders.merge-candidates', $availableOrder))->assertOk()->assertJsonCount(0, 'orders');
            $this->postJson(route('orders.merge', $editingOrder), ['target_order_id' => $availableOrder->public_id])->assertStatus(409);
            $this->postJson(route('orders.merge', $availableOrder), ['target_order_id' => $editingOrder->public_id])->assertStatus(409);
        }

        $this->assertSame($originalItems, $editingOrder->fresh()->items);
        $this->assertSame($originalCost, $editingOrder->fresh()->total_cost);
        $this->assertSame('new', $availableOrder->fresh()->status);
        $this->assertDatabaseCount('order_histories', 0);
        $this->actingAs($owner)->deleteJson(route('orders.edit-lock.release', $editingOrder), ['edit_lock_token' => $token])->assertOk();
        $this->getJson(route('orders.merge-candidates', $availableOrder))->assertOk()->assertJsonPath('orders.0.id', $editingOrder->public_id);
        $this->postJson(route('orders.merge', $availableOrder), ['target_order_id' => $editingOrder->public_id])->assertOk();
    }

    public function test_merge_becomes_available_after_edit_lock_timeout(): void
    {
        $owner = $this->createAdminUser();
        $client = Client::factory()->create();
        $source = Order::factory()->create(['client_id' => $client->id, 'status' => 'new', 'payments_total' => 0]);
        $target = Order::factory()->create(['client_id' => $client->id, 'status' => 'new', 'payments_total' => 0]);
        $this->actingAs($owner)->get(route('orders.edit', $target))->assertOk();
        $this->travel(5)->minutes();
        $this->getJson(route('orders.merge-candidates', $source))->assertOk()->assertJsonPath('orders.0.id', $target->public_id);
        $this->postJson(route('orders.merge', $source), ['target_order_id' => $target->public_id])->assertOk();
        $this->assertSame('cancelled', $source->fresh()->status);
    }
}
