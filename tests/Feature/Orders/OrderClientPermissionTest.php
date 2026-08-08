<?php

namespace Tests\Feature\Orders;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesRoles;
use Tests\TestCase;

class OrderClientPermissionTest extends TestCase
{
    use CreatesRoles;
    use RefreshDatabase;

    public function test_role_form_contains_granular_order_and_client_permissions(): void
    {
        $this->actingAs($this->createAdminUser())
            ->get(route('admin.roles.create'))
            ->assertOk()
            ->assertDontSee('Доступність замовлень')
            ->assertSee('Замовлення')
            ->assertSee('name="analytics_orders_access"', false)
            ->assertSee('name="orders_scope"', false)
            ->assertSee('name="orders_update"', false)
            ->assertSee('name="orders_payments"', false)
            ->assertSee('name="orders_payments_overpayment"', false)
            ->assertSee('name="orders_payments_edit"', false)
            ->assertSee('name="orders_clients_create"', false)
            ->assertSee('name="orders_clients_edit"', false)
            ->assertSee('name="orders_clients_payments"', false)
            ->assertSee('name="orders_clients_overpayments_manage"', false)
            ->assertSee('name="orders_clients_payments_edit"', false)
            ->assertSee('Списати з переплати')
            ->assertSee('Керування переплатами')
            ->assertSee('Редагування платежів')
            ->assertDontSee('Керування замовниками');
    }

    public function test_order_access_can_be_hidden_and_own_scope_is_enforced_everywhere(): void
    {
        $withoutOrders = $this->createUserWithRole([
            'can_orders' => true,
            'orders_proposals' => true,
            'orders_access' => false,
            'orders_clients_manage' => true,
            'orders_clients_create' => false,
            'orders_clients_edit' => false,
            'orders_clients_payments' => false,
        ]);
        $client = Client::factory()->create();

        $this->actingAs($withoutOrders)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertDontSee(route('orders.create'), false)
            ->assertDontSee('<table class="orders-table', false);
        $this->actingAs($withoutOrders)->get(route('orders.create'))->assertForbidden();

        $owner = $this->createUserWithRole([
            'can_orders' => true,
            'orders_proposals' => true,
            'orders_access' => true,
            'orders_scope' => 'own',
            'orders_update' => false,
            'orders_payments' => false,
            'orders_clients_manage' => true,
            'orders_clients_create' => false,
            'orders_clients_edit' => false,
            'orders_clients_payments' => false,
        ]);
        $ownOrder = Order::factory()->create([
            'client_id' => $client->id,
            'created_by' => $owner->id,
            'customer_name' => 'Власне замовлення',
        ]);
        $otherOrder = Order::factory()->create([
            'client_id' => $client->id,
            'created_by' => User::factory(),
            'customer_name' => 'Чуже замовлення',
        ]);

        $this->actingAs($owner)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($ownOrder->order_number)
            ->assertDontSee($otherOrder->order_number);
        $this->actingAs($owner)->get(route('orders.show', $ownOrder))->assertOk();
        $this->actingAs($owner)->get(route('orders.show', $otherOrder))->assertForbidden();
        $this->actingAs($owner)->get(route('orders.pdf', $ownOrder))->assertOk();
        $this->actingAs($owner)->get(route('orders.pdf', $otherOrder))->assertForbidden();

        $this->actingAs($owner)
            ->get(route('orders.clients.show', ['client' => $client, 'section' => 'orders']))
            ->assertOk()
            ->assertSee($ownOrder->order_number)
            ->assertDontSee($otherOrder->order_number);
    }

    public function test_order_edit_history_and_payments_require_their_own_permissions(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_proposals' => true,
            'orders_access' => true,
            'orders_scope' => 'all',
            'orders_update' => false,
            'orders_payments' => false,
        ]);
        $client = Client::factory()->create();
        $order = Order::factory()->create(['client_id' => $client->id]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee(route('orders.edit', $order), false)
            ->assertDontSee('Історія змін замовлення')
            ->assertDontSee('title="Відкрити платежі замовлення"', false);
        $this->actingAs($user)->get(route('orders.edit', $order))->assertForbidden();
        $this->actingAs($user)->get(route('orders.history', $order))->assertForbidden();
        $this->actingAs($user)->get(route('orders.payments.exchange-rates'))->assertForbidden();
        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                'return_context' => 'order',
            ])
            ->assertForbidden();
    }

    public function test_client_base_create_edit_and_payment_permissions_are_independent(): void
    {
        $viewer = $this->createUserWithRole([
            'can_orders' => true,
            'orders_access' => false,
            'orders_clients_manage' => true,
            'orders_clients_create' => false,
            'orders_clients_edit' => false,
            'orders_clients_payments' => false,
        ]);
        $client = Client::factory()->create();

        $this->actingAs($viewer)
            ->get(route('orders.clients.index'))
            ->assertOk()
            ->assertDontSee(route('orders.clients.create'), false);
        $this->actingAs($viewer)
            ->get(route('orders.clients.show', $client))
            ->assertOk()
            ->assertDontSee(route('orders.clients.edit', $client), false)
            ->assertDontSee('class="fixed inset-0 z-[14000]', false)
            ->assertDontSee('<table class="client-orders-table', false);
        $this->actingAs($viewer)->get(route('orders.clients.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('orders.clients.edit', $client))->assertForbidden();
        $this->actingAs($viewer)
            ->postJson(route('orders.clients.payments.store', $client), [])
            ->assertForbidden();
    }

    public function test_order_payment_permission_cannot_be_used_for_client_prepayments_or_foreign_orders(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_proposals' => true,
            'orders_access' => true,
            'orders_scope' => 'own',
            'orders_update' => false,
            'orders_payments' => true,
            'orders_clients_manage' => false,
            'orders_clients_payments' => false,
        ]);
        $client = Client::factory()->create();
        $ownOrder = Order::factory()->create(['client_id' => $client->id, 'created_by' => $user->id]);
        $foreignOrder = Order::factory()->create(['client_id' => $client->id, 'created_by' => User::factory()]);
        $payload = [
            'amount' => 100,
            'amount_uah' => 100,
            'currency' => 'UAH',
            'payment_date' => now('Europe/Kiev')->format('Y-m-d'),
            'payment_time' => now('Europe/Kiev')->format('H:i'),
            'payment_type' => 'order',
            'payment_source' => 'direct',
            'return_context' => 'order',
        ];

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'payment_type' => 'prepayment',
                'order_public_id' => null,
            ])
            ->assertUnprocessable();

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'order_public_id' => $foreignOrder->public_id,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'order_public_id' => $ownOrder->public_id,
            ])
            ->assertOk();

        $this->assertSame(1, ClientPayment::query()->count());
    }

    public function test_order_payment_overpayment_and_edit_permissions_are_enforced_separately(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_proposals' => true,
            'orders_access' => true,
            'orders_scope' => 'all',
            'orders_payments' => true,
            'orders_payments_overpayment' => false,
            'orders_payments_edit' => false,
        ]);
        $client = Client::factory()->create();
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'total_cost' => 1000,
            'payments_total' => 0,
            'amount_due' => 1000,
        ]);
        ClientPayment::query()->create([
            'client_id' => $client->id,
            'amount' => 1000,
            'amount_uah' => 1000,
            'currency' => 'UAH',
            'payment_type' => 'prepayment',
            'paid_at' => now(),
        ]);
        $existingPayment = ClientPayment::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'amount' => 100,
            'amount_uah' => 100,
            'currency' => 'UAH',
            'payment_type' => 'order',
            'paid_at' => now(),
        ]);
        $payload = [
            'amount' => 100,
            'amount_uah' => 100,
            'currency' => 'UAH',
            'payment_date' => now('Europe/Kiev')->format('Y-m-d'),
            'payment_time' => now('Europe/Kiev')->format('H:i'),
            'payment_type' => 'order',
            'order_public_id' => $order->public_id,
            'return_context' => 'order',
        ];

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Історія платежів замовлення')
            ->assertSee('Внести платіж')
            ->assertDontSee('Списати з переплати')
            ->assertDontSee('isReadOnlyPayment && canEditViewedPayment', false);

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'payment_source' => 'direct',
            ])
            ->assertOk();
        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'payment_source' => 'overpayment',
            ])
            ->assertForbidden();
        $this->actingAs($user)
            ->patchJson(route('orders.clients.payments.update', [$client, $existingPayment]), [
                ...$payload,
                'payment_source' => 'direct',
            ])
            ->assertForbidden();

        $manager = $this->createUserWithRole([
            'can_orders' => true,
            'orders_proposals' => true,
            'orders_access' => true,
            'orders_scope' => 'all',
            'orders_payments' => true,
            'orders_payments_overpayment' => true,
            'orders_payments_edit' => true,
        ]);

        $this->actingAs($manager)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Списати з переплати')
            ->assertSee('isReadOnlyPayment && canEditViewedPayment', false);
        $this->actingAs($manager)
            ->patchJson(route('orders.clients.payments.update', [$client, $existingPayment]), [
                ...$payload,
                'amount' => 150,
                'amount_uah' => 150,
                'payment_source' => 'direct',
            ])
            ->assertOk();
    }

    public function test_client_payment_overpayment_and_edit_permissions_are_enforced_separately(): void
    {
        $viewer = $this->createUserWithRole([
            'can_orders' => true,
            'orders_access' => false,
            'orders_clients_manage' => true,
            'orders_clients_payments' => true,
            'orders_clients_overpayments_manage' => false,
            'orders_clients_payments_edit' => false,
        ]);
        $client = Client::factory()->create();
        $order = Order::factory()->create(['client_id' => $client->id, 'total_cost' => 1000]);
        ClientPayment::query()->create([
            'client_id' => $client->id,
            'amount' => 500,
            'amount_uah' => 500,
            'currency' => 'UAH',
            'payment_type' => 'prepayment',
            'paid_at' => now(),
        ]);
        $payload = [
            'amount' => 100,
            'amount_uah' => 100,
            'currency' => 'UAH',
            'payment_date' => now('Europe/Kiev')->format('Y-m-d'),
            'payment_time' => now('Europe/Kiev')->format('H:i'),
            'payment_type' => 'order',
            'payment_source' => 'direct',
            'order_public_id' => $order->public_id,
        ];

        $this->actingAs($viewer)
            ->get(route('orders.clients.show', ['client' => $client, 'section' => 'payments']))
            ->assertOk()
            ->assertSee('Внести платіж')
            ->assertSee('Платежі')
            ->assertDontSee('data-client-overpayment-total', false)
            ->assertDontSee('@click="openOverpaymentPayment()"', false)
            ->assertSee('canManageOverpayments: false', false)
            ->assertSee(':disabled="!canManageOverpayments"', false)
            ->assertDontSee('isReadOnlyPayment && canEditViewedPayment', false);

        $this->actingAs($viewer)
            ->postJson(route('orders.clients.payments.store', $client), $payload)
            ->assertOk();
        $directPayment = ClientPayment::query()->where('order_id', $order->id)->latest('id')->firstOrFail();
        $this->actingAs($viewer)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'payment_type' => 'prepayment',
                'order_public_id' => null,
            ])
            ->assertForbidden();
        $this->actingAs($viewer)
            ->patchJson(route('orders.clients.payments.update', [$client, $directPayment]), $payload)
            ->assertForbidden();

        $manager = $this->createUserWithRole([
            'can_orders' => true,
            'orders_access' => false,
            'orders_clients_manage' => true,
            'orders_clients_payments' => true,
            'orders_clients_overpayments_manage' => true,
            'orders_clients_payments_edit' => true,
        ]);
        $this->actingAs($manager)
            ->get(route('orders.clients.show', ['client' => $client, 'section' => 'payments']))
            ->assertOk()
            ->assertSee('data-client-overpayment-total', false)
            ->assertSee('@click="openOverpaymentPayment()"', false)
            ->assertSee('canManageOverpayments: true', false)
            ->assertSee('isReadOnlyPayment && canEditViewedPayment', false);
        $this->actingAs($manager)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'payment_type' => 'prepayment',
                'order_public_id' => null,
            ])
            ->assertOk();
        $this->actingAs($manager)
            ->patchJson(route('orders.clients.payments.update', [$client, $directPayment]), [
                ...$payload,
                'amount' => 120,
                'amount_uah' => 120,
            ])
            ->assertOk();
    }
}
