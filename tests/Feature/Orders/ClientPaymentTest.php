<?php

namespace Tests\Feature\Orders;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\Support\CreatesRoles;
use Tests\TestCase;

class ClientPaymentTest extends TestCase
{
    use CreatesRoles;
    use RefreshDatabase;

    public function test_client_card_displays_payment_controls_modal_and_only_client_orders(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create(['name' => 'Марія Прінт']);
        $otherClient = Client::factory()->create();
        $clientOrder = Order::factory()->create(['client_id' => $client->id]);
        $otherOrder = Order::factory()->create(['client_id' => $otherClient->id]);

        $this->actingAs($user)
            ->get(route('orders.clients.edit', ['client' => $client, 'section' => 'payments']))
            ->assertOk()
            ->assertSee('Картка клієнта. Марія Прінт')
            ->assertDontSee('Списати з переплати')
            ->assertSee('Внести платіж')
            ->assertSee('Відобразити код платежу')
            ->assertSee('x-show="showPaymentCodes"', false)
            ->assertSee('Переплата')
            ->assertSee('Оплата замовлення')
            ->assertSee('type="date"', false)
            ->assertSee(':max="today"', false)
            ->assertSee('type="time"', false)
            ->assertSee('w-[1100px]', false)
            ->assertSee('md:grid-cols-4', false)
            ->assertSee('data-payment-form-panel', false)
            ->assertSee('rounded-lg border border-gray-200 bg-gray-50 p-5', false)
            ->assertSee('Дані платежу')
            ->assertSee('window.confirm', false)
            ->assertSee('@focus="loadPaymentOrders()"', false)
            ->assertSee('this.showPaymentModal = true;', false)
            ->assertSee('this.loadPaymentOrders();', false)
            ->assertDontSee(':disabled="isLoadingPaymentOrders"', false)
            ->assertSee("cache: 'no-store'", false)
            ->assertSee('paymentOrdersUrl:', false)
            ->assertSee('paymentStoreUrl:', false)
            ->assertDontSee('data-client-overpayment-total', false);

        $newOrder = Order::factory()->create(['client_id' => $client->id]);

        $this->actingAs($user)
            ->getJson(route('orders.clients.payments.orders', $client))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonFragment(['id' => $clientOrder->public_id, 'number' => $clientOrder->order_number])
            ->assertJsonFragment(['id' => $newOrder->public_id, 'number' => $newOrder->order_number])
            ->assertJsonMissing(['id' => $otherOrder->public_id, 'number' => $otherOrder->order_number]);
    }

    public function test_user_can_add_prepayment_and_invalid_amount_or_future_date_is_rejected(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $today = now('Europe/Kiev')->toDateString();

        $response = $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                'amount' => 1250,
                'currency' => 'UAH',
                'payment_date' => $today,
                'payment_time' => '12:34',
                'payment_type' => 'prepayment',
                'comment' => 'Передплата готівкою',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect_url', route('orders.clients.edit', [
                'client' => $client,
                'section' => 'payments',
            ]));

        $payment = ClientPayment::query()->sole();
        $this->assertTrue(Str::isUuid($payment->public_id));
        $this->assertSame($client->id, $payment->client_id);
        $this->assertNull($payment->order_id);
        $this->assertSame(1250, $payment->amount);
        $this->assertSame('UAH', $payment->currency);
        $this->assertSame('prepayment', $payment->payment_type);
        $this->assertSame($user->id, $payment->created_by);
        $this->assertSame('12:34', $payment->paid_at->copy()->timezone('Europe/Kiev')->format('H:i'));
        $this->assertSame($payment->public_id, $response->json('payment_id'));

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                'amount' => 0,
                'currency' => 'UAH',
                'payment_date' => now('Europe/Kiev')->addDay()->toDateString(),
                'payment_time' => '12:00',
                'payment_type' => 'prepayment',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount', 'payment_date']);

        $this->assertDatabaseCount('client_payments', 1);
    }

    public function test_client_card_displays_sum_of_overpayment_type_payments_under_the_title(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create(['name' => 'Клієнт з переплатою']);
        $order = Order::factory()->create(['client_id' => $client->id]);

        foreach ([['prepayment', 300, 'UAH', null], ['prepayment', 200, 'USD', null], ['order', 900, 'UAH', $order->id]] as [$type, $amount, $currency, $orderId]) {
            ClientPayment::query()->create([
                'client_id' => $client->id,
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => $currency,
                'payment_type' => $type,
                'paid_at' => now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }

        $this->actingAs($user)
            ->get(route('orders.clients.show', $client))
            ->assertOk()
            ->assertSee('data-client-overpayment-total', false)
            ->assertSee('Переплата: 500')
            ->assertDontSee('Переплата: 1 400');
    }

    public function test_overpayment_can_be_safely_applied_to_an_order_and_reduces_client_balance(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create(['name' => 'Клієнт із залишком']);
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'total_cost' => 1000,
            'payments_total' => 0,
            'amount_due' => 1000,
        ]);
        $overpayment = ClientPayment::query()->create([
            'client_id' => $client->id,
            'amount' => 1000,
            'currency' => 'UAH',
            'payment_type' => 'prepayment',
            'paid_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $payload = [
            'currency' => 'UAH',
            'payment_date' => now('Europe/Kiev')->toDateString(),
            'payment_time' => '10:30',
            'payment_type' => 'order',
            'payment_source' => 'overpayment',
            'order_public_id' => $order->public_id,
        ];

        $this->actingAs($user)
            ->get(route('orders.clients.show', ['client' => $client, 'section' => 'payments']))
            ->assertOk()
            ->assertSee('Переплата: 1 000')
            ->assertSee('@click="openOverpaymentPayment()"', false)
            ->assertSee('Списати з переплати')
            ->assertSee('Внесення платежу з переплати')
            ->assertSee('x-show="!paymentForm.fromOverpayment"', false);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('@click="submitPayment(true)"', false)
            ->assertSee('Списати з переплати');

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'amount' => 400,
            ])
            ->assertOk();

        $appliedPayment = ClientPayment::query()->where('is_from_overpayment', true)->sole();
        $this->assertSame($order->id, $appliedPayment->order_id);
        $this->assertSame(400, $appliedPayment->amount);
        $this->assertSame('400.00', $order->fresh()->payments_total);
        $this->assertSame('600.00', $order->fresh()->amount_due);

        $this->actingAs($user)
            ->get(route('orders.clients.show', ['client' => $client, 'section' => 'payments']))
            ->assertOk()
            ->assertSee('Переплата: 600')
            ->assertSee('з переплати');

        $this->actingAs($user)
            ->get(route('orders.show', ['order' => $order, 'payments' => 1]))
            ->assertOk()
            ->assertSee('з переплати')
            ->assertSee('Частково сплачено');

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'amount' => 700,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->actingAs($user)
            ->patchJson(route('orders.clients.payments.update', [$client, $appliedPayment]), [
                ...$payload,
                'amount' => 600,
            ])
            ->assertOk();

        $this->assertSame(600, $appliedPayment->fresh()->amount);
        $this->assertSame('600.00', $order->fresh()->payments_total);
        $this->assertSame('400.00', $order->fresh()->amount_due);

        $this->actingAs($user)
            ->patchJson(route('orders.clients.payments.update', [$client, $overpayment]), [
                'amount' => 500,
                'currency' => 'UAH',
                'payment_date' => now('Europe/Kiev')->toDateString(),
                'payment_time' => '10:30',
                'payment_type' => 'prepayment',
                'payment_source' => 'direct',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->assertSame(1000, $overpayment->fresh()->amount);
    }

    public function test_order_payment_popup_creates_shared_payment_and_returns_to_same_order(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create(['name' => 'Спільний клієнт']);
        $order = Order::factory()->create(['client_id' => $client->id]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('@click="$dispatch(\'open-order-payments\')"', false)
            ->assertSee('Платежі замовлення ${orderNumber}', false)
            ->assertSee('Історія платежів замовлення')
            ->assertSee('type="date"', false)
            ->assertSee(':max="today"', false)
            ->assertSee('type="time"', false)
            ->assertSee('window.confirm', false)
            ->assertSee('x-ref="paymentEditor"', false)
            ->assertSee("scrollIntoView({ behavior: 'smooth', block: 'start' })", false)
            ->assertSee('x-show="!isEditing"', false)
            ->assertSee('Повернутися до платежів');

        $response = $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                'amount' => 750,
                'currency' => 'UAH',
                'payment_date' => now('Europe/Kiev')->toDateString(),
                'payment_time' => '11:25',
                'payment_type' => 'order',
                'order_public_id' => $order->public_id,
                'comment' => 'Внесено із замовлення',
                'return_context' => 'order',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect_url', route('orders.show', [
                'order' => $order,
                'payments' => 1,
            ]));

        $payment = ClientPayment::query()->sole();
        $this->assertSame($client->id, $payment->client_id);
        $this->assertSame($order->id, $payment->order_id);
        $this->assertSame($payment->public_id, $response->json('payment_id'));

        $this->actingAs($user)
            ->get(route('orders.show', ['order' => $order, 'payments' => 1]))
            ->assertOk()
            ->assertSee('openOnLoad: true', false)
            ->assertSee('750')
            ->assertSee($order->order_number)
            ->assertSee($user->name);

        $this->actingAs($user)
            ->get(route('orders.clients.edit', ['client' => $client, 'section' => 'payments']))
            ->assertOk()
            ->assertSee('750')
            ->assertSee($order->order_number)
            ->assertSee($user->name);
    }

    public function test_order_payment_status_and_amount_due_follow_all_linked_payments_without_currency_conversion(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'total_cost' => 1000,
            'payments_total' => 0,
            'amount_due' => 1000,
        ]);
        $basePayload = [
            'payment_date' => now('Europe/Kiev')->toDateString(),
            'payment_time' => '12:30',
            'payment_type' => 'order',
            'order_public_id' => $order->public_id,
            'return_context' => 'order',
        ];

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Не сплачено')
            ->assertSee('border-red-300 bg-rose-100 text-red-800', false)
            ->assertSee('text-red-700', false)
            ->assertSee('Загальна сума сплат');

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$basePayload,
                'amount' => 400,
                'currency' => 'USD',
            ])
            ->assertOk();

        $order->refresh();
        $this->assertSame('400.00', $order->payments_total);
        $this->assertSame('600.00', $order->amount_due);
        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Частково сплачено')
            ->assertSee('border-orange-500 bg-yellow-100 text-orange-800', false)
            ->assertSee('text-amber-600', false);

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$basePayload,
                'amount' => 600,
                'currency' => 'EUR',
            ])
            ->assertOk();

        $order->refresh();
        $this->assertSame('1000.00', $order->payments_total);
        $this->assertSame('0.00', $order->amount_due);
        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Сплачено')
            ->assertSee('border-green-300 bg-green-100 text-green-800', false);

        $secondPayment = ClientPayment::query()->latest('id')->firstOrFail();
        $this->actingAs($user)
            ->patchJson(route('orders.clients.payments.update', [$client, $secondPayment]), [
                ...$basePayload,
                'amount' => 800,
                'currency' => 'EUR',
            ])
            ->assertOk();

        $order->refresh();
        $this->assertSame('1200.00', $order->payments_total);
        $this->assertSame('-200.00', $order->amount_due);
        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Є переплата')
            ->assertSee('border-blue-400 bg-teal-100 text-blue-800', false)
            ->assertSee('text-blue-700', false)
            ->assertSee('-200');
    }

    public function test_order_payment_requires_an_order_owned_by_the_same_client(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $clientOrder = Order::factory()->create(['client_id' => $client->id]);
        $otherOrder = Order::factory()->create(['client_id' => $otherClient->id]);
        $payload = [
            'amount' => 500,
            'currency' => 'USD',
            'payment_date' => now('Europe/Kiev')->toDateString(),
            'payment_time' => '09:15',
            'payment_type' => 'order',
        ];

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_public_id');

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'order_public_id' => $otherOrder->public_id,
            ])
            ->assertUnprocessable();

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'order_public_id' => $clientOrder->public_id,
            ])
            ->assertOk();

        $this->assertSame($clientOrder->id, ClientPayment::query()->sole()->order_id);

        $this->actingAs($user)
            ->get(route('orders.show', $clientOrder))
            ->assertOk()
            ->assertViewHas('order', fn (Order $loadedOrder): bool => $loadedOrder->payments->count() === 1)
            ->assertSee('500')
            ->assertSee($user->name);
    }

    public function test_paid_orders_reject_new_payments_and_are_excluded_from_client_order_selector(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $orders = collect(range(1, 4))->map(fn (): Order => Order::factory()->create([
            'client_id' => $client->id,
            'total_cost' => 1000,
        ]));

        foreach ([1 => 400, 2 => 1000, 3 => 1200] as $orderIndex => $amount) {
            ClientPayment::query()->create([
                'client_id' => $client->id,
                'order_id' => $orders[$orderIndex]->id,
                'amount' => $amount,
                'currency' => 'UAH',
                'payment_type' => 'order',
                'paid_at' => now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }

        $this->actingAs($user)
            ->getJson(route('orders.clients.payments.orders', $client))
            ->assertOk()
            ->assertJsonCount(2, 'orders')
            ->assertJsonFragment(['id' => $orders[0]->public_id, 'number' => $orders[0]->order_number])
            ->assertJsonFragment(['id' => $orders[1]->public_id, 'number' => $orders[1]->order_number])
            ->assertJsonMissing(['id' => $orders[2]->public_id])
            ->assertJsonMissing(['id' => $orders[3]->public_id]);

        $this->actingAs($user)
            ->get(route('orders.show', $orders[2]))
            ->assertOk()
            ->assertSee('canAddPayment: false', false)
            ->assertSee('Новий платіж недоступний: замовлення вже сплачено або має переплату.');

        $this->actingAs($user)
            ->get(route('orders.show', $orders[1]))
            ->assertOk()
            ->assertSee('canAddPayment: true', false);

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                'amount' => 100,
                'currency' => 'UAH',
                'payment_date' => now('Europe/Kiev')->toDateString(),
                'payment_time' => '10:30',
                'payment_type' => 'order',
                'payment_source' => 'direct',
                'order_public_id' => $orders[2]->public_id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_public_id');
    }

    public function test_editing_payment_marks_row_yellow_and_records_every_changed_field(): void
    {
        $creator = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $editor = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $order = Order::factory()->create(['client_id' => $client->id]);
        $payment = ClientPayment::query()->create([
            'client_id' => $client->id,
            'amount' => 100,
            'currency' => 'UAH',
            'payment_type' => 'prepayment',
            'paid_at' => now()->subDay(),
            'comment' => 'Старий коментар',
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]);

        $this->actingAs($editor)
            ->patchJson(route('orders.clients.payments.update', [$client, $payment]), [
                'amount' => 250,
                'currency' => 'EUR',
                'payment_date' => now('Europe/Kiev')->toDateString(),
                'payment_time' => '14:45',
                'payment_type' => 'order',
                'order_public_id' => $order->public_id,
                'comment' => 'Новий коментар',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $payment->refresh();
        $this->assertTrue($payment->is_edited);
        $this->assertSame($creator->id, $payment->created_by);
        $this->assertSame($editor->id, $payment->updated_by);
        $this->assertSame($order->id, $payment->order_id);

        $history = $payment->histories()->with('user')->sole();
        $this->assertSame($editor->id, $history->user_id);
        $this->assertSame(
            ['Сума', 'Валюта', 'Дата та час', 'Тип платежу', 'Номер замовлення', 'Коментар'],
            collect($history->changes)->pluck('label')->all()
        );

        $this->actingAs($editor)
            ->get(route('orders.clients.edit', ['client' => $client, 'section' => 'payments']))
            ->assertOk()
            ->assertSee('bg-yellow-100', false)
            ->assertSee('Історія змін платежу')
            ->assertSee($creator->name)
            ->assertSee($order->order_number);

        $this->actingAs($editor)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('bg-yellow-100', false)
            ->assertViewHas('order', function (Order $loadedOrder) use ($payment): bool {
                $loadedPayment = $loadedOrder->payments->firstWhere('id', $payment->id);

                return $loadedPayment !== null && $loadedPayment->histories->count() === 1;
            });
    }

    public function test_payment_from_another_client_cannot_be_edited(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $payment = ClientPayment::query()->create([
            'client_id' => $otherClient->id,
            'amount' => 100,
            'currency' => 'UAH',
            'payment_type' => 'prepayment',
            'paid_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->patchJson(route('orders.clients.payments.update', [$client, $payment]), [
                'amount' => 200,
                'currency' => 'UAH',
                'payment_date' => now('Europe/Kiev')->toDateString(),
                'payment_time' => '10:00',
                'payment_type' => 'prepayment',
            ])
            ->assertNotFound();

        $this->assertSame(100, $payment->fresh()->amount);
    }

    public function test_payment_has_no_direct_page_and_rejects_unsupported_or_numeric_routes(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $payment = ClientPayment::query()->create([
            'client_id' => $client->id,
            'amount' => 100,
            'currency' => 'UAH',
            'payment_type' => 'prepayment',
            'paid_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $updateUrl = route('orders.clients.payments.update', [$client, $payment]);

        $this->assertFalse(Route::has('orders.clients.payments.show'));

        $this->actingAs($user)->get($updateUrl)->assertMethodNotAllowed();
        $this->actingAs($user)->postJson($updateUrl, [])->assertMethodNotAllowed();
        $this->actingAs($user)->deleteJson($updateUrl)->assertMethodNotAllowed();

        $numericUrl = '/orders/clients/'.$client->public_id.'/payments/'.$payment->id;
        $this->actingAs($user)
            ->patchJson($numericUrl, [
                'amount' => 200,
                'currency' => 'UAH',
                'payment_date' => now('Europe/Kiev')->toDateString(),
                'payment_time' => '10:00',
                'payment_type' => 'prepayment',
            ])
            ->assertNotFound();

        $this->assertSame(100, $payment->fresh()->amount);
    }
}
