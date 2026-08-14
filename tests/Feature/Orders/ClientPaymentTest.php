<?php

namespace Tests\Feature\Orders;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\Support\CreatesRoles;
use Tests\TestCase;

class ClientPaymentTest extends TestCase
{
    use CreatesRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('payments.privatbank.sale-rates.v1');
        Http::fake([
            'api.privatbank.ua/*' => Http::response([
                ['ccy' => 'EUR', 'base_ccy' => 'UAH', 'buy' => '45.50000', 'sale' => '46.50000'],
                ['ccy' => 'USD', 'base_ccy' => 'UAH', 'buy' => '40.25000', 'sale' => '41.25000'],
            ]),
        ]);
    }

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
            ->assertSee('Оплата замовлення')
            ->assertSee('Внесення переплати')
            ->assertSeeInOrder(['Оплата замовлення', 'Внесення переплати'])
            ->assertSee("paymentType: 'order'", false)
            ->assertSee('type="date"', false)
            ->assertSee(':max="today"', false)
            ->assertSee('type="time"', false)
            ->assertSee('w-[1100px]', false)
            ->assertSee('fixed inset-0 z-[14000] !mt-0', false)
            ->assertSee('md:grid-cols-4', false)
            ->assertSee('data-payment-form-panel', false)
            ->assertSee('rounded-lg border border-gray-200 bg-gray-50 p-5', false)
            ->assertSee('Дані платежу')
            ->assertSee('Еквівалент у ГРН')
            ->assertSee('SALE:', false)
            ->assertSee('ratesUrl:', false)
            ->assertSee('window.confirm', false)
            ->assertSee('Сума поповнення переплати')
            ->assertSee('Сума платежу буде списана з переплати клієнта.')
            ->assertSee('@focus="loadPaymentOrders()"', false)
            ->assertSee('@change="paymentOrderChanged()"', false)
            ->assertSee('applySelectedOrderAmount()', false)
            ->assertSee('Math.ceil(amountDue / rate)', false)
            ->assertSee('При внесені платежу, дельта переплати за замовлення буде зарахована до переплати Клієнта')
            ->assertSee('Значення суми операції більше за наявну переплату')
            ->assertSee('isSavingPayment || isOverpaymentOrderAmountInvalid()', false)
            ->assertSee('openViewPayment(', false)
            ->assertSee('isReadOnlyPayment && canEditViewedPayment', false)
            ->assertSee('Для внесеного платежу, дельта переплати за замовлення була зарахована до переплати Клієнта', false)
            ->assertSee('this.showPaymentModal = true;', false)
            ->assertSee('this.loadPaymentOrders();', false)
            ->assertDontSee(':disabled="isLoadingPaymentOrders"', false)
            ->assertSee("cache: 'no-store'", false)
            ->assertSee('paymentOrdersUrl:', false)
            ->assertSee('paymentStoreUrl:', false)
            ->assertSee('Сума до сплати: ${amountDue} ГРН', false)
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

    public function test_privatbank_sale_rates_are_cached_and_currency_payment_saves_uah_snapshot(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();

        $this->actingAs($user)
            ->getJson(route('orders.payments.exchange-rates'))
            ->assertOk()
            ->assertJsonPath('rates.USD', 41.25)
            ->assertJsonPath('rates.EUR', 46.5)
            ->assertJsonPath('source', 'PrivatBank')
            ->assertJsonPath('type', 'SALE');

        $this->actingAs($user)
            ->getJson(route('orders.payments.exchange-rates'))
            ->assertOk();

        Http::assertSentCount(1);

        $response = $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                'amount' => 3,
                'amount_uah' => 125,
                'currency' => 'USD',
                'payment_date' => now('Europe/Kiev')->toDateString(),
                'payment_time' => '12:00',
                'payment_type' => 'prepayment',
                'payment_source' => 'direct',
                'comment' => 'Платіж у валюті',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('notification', fn ($value): bool => str_contains($value, 'Курс SALE ПриватБанку')
                && str_contains($value, 'Сума поповнення переплати'));

        $payment = ClientPayment::query()->sole();
        $this->assertSame(3, $payment->amount);
        $this->assertSame(125, $payment->amount_uah);
        $this->assertSame(124, $payment->calculated_amount_uah);
        $this->assertSame('41.250000', $payment->exchange_rate);
        $this->assertSame('SALE', $payment->exchange_rate_type);
        $this->assertSame('PrivatBank', $payment->exchange_rate_source);
        $this->assertNotNull($payment->exchange_rate_fetched_at);
        $this->assertStringContainsString('Автоматично розрахована сума', $payment->comment);
        $this->assertStringContainsString('Користувачем встановлено суму 125 грн', $payment->comment);
    }

    public function test_currency_is_forbidden_when_spending_client_overpayment(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $order = Order::factory()->create(['client_id' => $client->id]);
        ClientPayment::query()->create([
            'client_id' => $client->id,
            'amount' => 1000,
            'currency' => 'UAH',
            'payment_type' => 'prepayment',
            'paid_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                'amount' => 10,
                'amount_uah' => 403,
                'currency' => 'USD',
                'payment_date' => now('Europe/Kiev')->toDateString(),
                'payment_time' => '12:00',
                'payment_type' => 'order',
                'payment_source' => 'overpayment',
                'order_public_id' => $order->public_id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency');
    }

    public function test_editing_currency_payment_replaces_generated_comment_blocks_instead_of_appending_them(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $basePayload = [
            'amount' => 10,
            'currency' => 'USD',
            'payment_date' => now('Europe/Kiev')->toDateString(),
            'payment_time' => '12:15',
            'payment_type' => 'prepayment',
            'payment_source' => 'direct',
        ];

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$basePayload,
                'amount_uah' => 500,
                'comment' => 'Коментар користувача',
            ])
            ->assertOk();

        $payment = ClientPayment::query()->sole();
        $this->assertSame(1, substr_count((string) $payment->comment, 'Курс SALE ПриватБанку на'));
        $this->assertStringContainsString('Користувачем встановлено суму 500 грн', (string) $payment->comment);

        $legacyBlock = 'Курс BUY ПриватБанку на 01.01.2026 10:00 становив 40,000000 грн/USD. '
            .'Автоматично розрахована сума у полі «Сума списання (ГРН)» за 10 доларів була 400 грн.';
        $this->actingAs($user)
            ->patchJson(route('orders.clients.payments.update', [$client, $payment]), [
                ...$basePayload,
                'amount_uah' => 550,
                'comment' => $payment->comment."\n\n".$legacyBlock,
            ])
            ->assertOk();

        $payment->refresh();
        $this->assertSame(1, substr_count((string) $payment->comment, 'Курс SALE ПриватБанку на'));
        $this->assertStringNotContainsString('Курс BUY ПриватБанку', (string) $payment->comment);
        $this->assertStringNotContainsString('Користувачем встановлено суму 500 грн', (string) $payment->comment);
        $this->assertStringContainsString('Користувачем встановлено суму 550 грн', (string) $payment->comment);
        $this->assertSame(1, substr_count((string) $payment->comment, 'Коментар користувача'));

        $firstHistoryCommentChange = collect($payment->histories()->oldest('id')->firstOrFail()->changes)
            ->firstWhere('field', 'comment');
        $this->assertStringContainsString('500 грн', (string) ($firstHistoryCommentChange['before'] ?? ''));
        $this->assertStringContainsString('550 грн', (string) ($firstHistoryCommentChange['after'] ?? ''));

        $this->actingAs($user)
            ->patchJson(route('orders.clients.payments.update', [$client, $payment]), [
                ...$basePayload,
                'amount_uah' => 413,
                'comment' => $payment->comment,
            ])
            ->assertOk();

        $payment->refresh();
        $this->assertSame('Коментар користувача', $payment->comment);
        $this->assertCount(2, $payment->histories);
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
            ->assertSee('Платіж з переплати')
            ->assertSee('Платіж за замовлення')
            ->assertSee('Просте списання')
            ->assertSee("setPaymentType('writeoff')", false)
            ->assertSee('Щонайменше 3 букви або цифри')
            ->assertSee('Сума операції')
            ->assertSee('Валюта операції')
            ->assertSee('<option value="UAH">UAH</option>', false)
            ->assertSee('x-show="!paymentForm.fromOverpayment"', false)
            ->assertSee(':readonly="isOverpaymentAmountReadOnly()"', false)
            ->assertSee('isSingleOverpaymentOrderMode()', false)
            ->assertSee('type="checkbox"', false)
            ->assertSee('selectedOverpaymentOrderIds', false)
            ->assertSee('order_public_ids:', false)
            ->assertSee('Максимально допустима загальна сума:', false)
            ->assertSee('Загальна сума вибраних замовлень перевищує доступну переплату клієнта.', false);

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

    public function test_simple_overpayment_writeoff_requires_descriptive_comment_and_no_order(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create(['name' => 'Клієнт із переплатою']);
        ClientPayment::query()->create([
            'client_id' => $client->id,
            'amount' => 1000,
            'currency' => 'UAH',
            'payment_type' => 'prepayment',
            'paid_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $payload = [
            'amount' => 250,
            'currency' => 'UAH',
            'payment_date' => now('Europe/Kiev')->toDateString(),
            'payment_time' => '11:15',
            'payment_type' => 'writeoff',
            'payment_source' => 'overpayment',
            'order_public_id' => null,
        ];

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'comment' => '!!12',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('comment');

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'comment' => 'А-1-Б',
            ])
            ->assertOk();

        $writeoff = ClientPayment::query()->where('payment_type', 'writeoff')->sole();
        $this->assertNull($writeoff->order_id);
        $this->assertTrue($writeoff->is_from_overpayment);
        $this->assertSame(250, $writeoff->amount_uah);

        $this->actingAs($user)
            ->get(route('orders.clients.show', ['client' => $client, 'section' => 'payments']))
            ->assertOk()
            ->assertSee('Переплата: 750')
            ->assertSee('Просте списання')
            ->assertSee('з переплати');

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'payment_source' => 'direct',
                'comment' => 'Достатньо довгий опис операції списання',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payment_type');
    }

    public function test_multiple_orders_are_paid_from_overpayment_as_separate_atomic_payments(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $orders = collect([
            Order::factory()->create(['client_id' => $client->id, 'total_cost' => 500, 'payments_total' => 0, 'amount_due' => 500]),
            Order::factory()->create(['client_id' => $client->id, 'total_cost' => 700, 'payments_total' => 0, 'amount_due' => 700]),
            Order::factory()->create(['client_id' => $client->id, 'total_cost' => 900, 'payments_total' => 0, 'amount_due' => 900]),
        ]);
        ClientPayment::query()->create([
            'client_id' => $client->id,
            'amount' => 2000,
            'amount_uah' => 2000,
            'currency' => 'UAH',
            'payment_type' => 'prepayment',
            'paid_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $payload = [
            // The server must not trust this browser-calculated total.
            'amount' => 1,
            'amount_uah' => 1,
            'currency' => 'UAH',
            'payment_date' => now('Europe/Kiev')->toDateString(),
            'payment_time' => '13:45',
            'payment_type' => 'order',
            'payment_source' => 'overpayment',
            'order_public_ids' => $orders->take(2)->pluck('public_id')->all(),
            'comment' => 'Одночасна оплата двох замовлень',
        ];

        $response = $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), $payload)
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('payments_count', 2)
            ->assertJsonPath('amount_total', 1200);

        $this->assertCount(2, $response->json('payment_ids'));
        foreach ($response->json('payment_ids') as $paymentId) {
            $this->assertTrue(Str::isUuid($paymentId));
        }
        foreach ($orders->take(2) as $order) {
            $this->assertDatabaseHas('client_payments', [
                'client_id' => $client->id,
                'order_id' => $order->id,
                'amount' => (int) $order->total_cost,
                'amount_uah' => (int) $order->total_cost,
                'currency' => 'UAH',
                'payment_type' => 'order',
                'is_from_overpayment' => true,
                'comment' => 'Одночасна оплата двох замовлень',
            ]);
            $this->assertSame((float) $order->total_cost, (float) $order->fresh()->payments_total);
            $this->assertSame(0.0, (float) $order->fresh()->amount_due);
        }
        $this->assertDatabaseMissing('client_payments', [
            'order_id' => $orders[2]->id,
            'is_from_overpayment' => true,
        ]);

        $paymentCount = ClientPayment::query()->count();
        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'amount' => 900,
                'amount_uah' => 900,
                'order_public_ids' => [$orders[2]->public_id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->assertSame($paymentCount, ClientPayment::query()->count());
        $this->assertSame(0.0, (float) $orders[2]->fresh()->payments_total);
    }

    public function test_single_selected_order_accepts_partial_amount_within_order_due_and_client_overpayment(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'total_cost' => 900,
            'payments_total' => 0,
            'amount_due' => 900,
        ]);
        ClientPayment::query()->create([
            'client_id' => $client->id,
            'amount' => 700,
            'amount_uah' => 700,
            'currency' => 'UAH',
            'payment_type' => 'prepayment',
            'paid_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $payload = [
            'amount' => 350,
            'amount_uah' => 350,
            'currency' => 'UAH',
            'payment_date' => now('Europe/Kiev')->toDateString(),
            'payment_time' => '13:45',
            'payment_type' => 'order',
            'payment_source' => 'overpayment',
            'order_public_ids' => [$order->public_id],
            'comment' => 'Часткова оплата одного замовлення',
        ];

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), $payload)
            ->assertOk()
            ->assertJsonPath('payments_count', 1)
            ->assertJsonPath('amount_total', 350);

        $this->assertDatabaseHas('client_payments', [
            'client_id' => $client->id,
            'order_id' => $order->id,
            'amount' => 350,
            'amount_uah' => 350,
            'is_from_overpayment' => true,
        ]);
        $this->assertSame(350.0, (float) $order->fresh()->payments_total);
        $this->assertSame(550.0, (float) $order->fresh()->amount_due);

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'amount' => 551,
                'amount_uah' => 551,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $otherOrder = Order::factory()->create([
            'client_id' => $client->id,
            'total_cost' => 500,
            'payments_total' => 0,
            'amount_due' => 500,
        ]);

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$payload,
                'amount' => 351,
                'amount_uah' => 351,
                'order_public_ids' => [$otherOrder->public_id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');
    }

    public function test_order_payment_popup_creates_shared_payment_and_returns_to_same_order(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create(['name' => 'Спільний клієнт']);
        $order = Order::factory()->create(['client_id' => $client->id, 'total_cost' => 1000]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('@click="$dispatch(\'open-order-payments\')"', false)
            ->assertSee('Сума операції')
            ->assertSee('Валюта операції')
            ->assertSee('Платежі замовлення ${orderNumber}', false)
            ->assertSee('<option value="UAH">UAH</option>', false)
            ->assertSee('fixed inset-0 z-[14000] !mt-0', false)
            ->assertSee('Історія платежів замовлення')
            ->assertSee('type="date"', false)
            ->assertSee(':max="today"', false)
            ->assertSee('type="time"', false)
            ->assertSee('window.confirm', false)
            ->assertSee('Сума платежу буде списана з переплати клієнта.')
            ->assertSee('x-ref="paymentEditor"', false)
            ->assertSee("scrollIntoView({ behavior: 'smooth', block: 'start' })", false)
            ->assertSee('x-show="!isEditing"', false)
            ->assertSee('Повернутися до платежів');
        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('orderAmountDue:', false)
            ->assertSee('applyOrderAmountSuggestion()', false)
            ->assertSee('Math.ceil(this.orderAmountDue / rate)', false)
            ->assertSee('При внесені платежу, дельта переплати за замовлення буде зарахована у переплату Клієнта')
            ->assertSee('Значення суми операції не може бути більшим за залишкову суму до сплати по замовленню')
            ->assertSee('isSaving || isOverpaymentSpendAmountInvalid()', false);
        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('viewPayment(', false)
            ->assertSee('<fieldset :disabled="isReadOnlyPayment || (paymentsBlocked && !isEditing)">', false)
            ->assertSee('x-show="isReadOnlyPayment && canEditViewedPayment"', false)
            ->assertSee('Для внесеного платежу, дельта переплати за замовлення була зарахована до переплати Клієнта', false);

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

    public function test_order_payment_status_and_amount_due_use_rounded_up_uah_conversion(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'total_cost' => 878,
            'payments_total' => 0,
            'amount_due' => 878,
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
                'amount' => 10,
                'amount_uah' => 413,
                'currency' => 'USD',
            ])
            ->assertOk();

        $order->refresh();
        $this->assertSame('413.00', $order->payments_total);
        $this->assertSame('465.00', $order->amount_due);
        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Частково сплачено')
            ->assertSee('border-orange-500 bg-yellow-100 text-orange-800', false)
            ->assertSee('text-amber-600', false);

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                ...$basePayload,
                'amount' => 10,
                'amount_uah' => 465,
                'currency' => 'EUR',
            ])
            ->assertOk();

        $order->refresh();
        $this->assertSame('878.00', $order->payments_total);
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
                'amount' => 20,
                'amount_uah' => 930,
                'currency' => 'EUR',
            ])
            ->assertOk();

        $order->refresh();
        $this->assertSame('878.00', $order->payments_total);
        $this->assertSame('0.00', $order->amount_due);
        $automaticOverpayment = ClientPayment::query()->where('is_automatic', true)->sole();
        $this->assertSame(465, $automaticOverpayment->amount_uah);
        $this->assertSame('prepayment', $automaticOverpayment->payment_type);
        $this->assertNull($automaticOverpayment->order_id);
        $this->assertSame($secondPayment->id, $automaticOverpayment->source_payment_id);
        $this->assertStringContainsString($secondPayment->public_id, (string) $automaticOverpayment->comment);
        $this->assertStringContainsString($order->order_number, (string) $automaticOverpayment->comment);
        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Сплачено');

        $this->actingAs($user)
            ->get(route('orders.clients.show', ['client' => $client, 'section' => 'payments']))
            ->assertOk()
            ->assertSee('Автоматично')
            ->assertSee('data-payment-comment-marker', false);
    }

    public function test_direct_order_overpayment_is_split_into_an_automatic_client_prepayment(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'total_cost' => 1000,
            'amount_due' => 1000,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                'amount' => 1200,
                'amount_uah' => 1200,
                'currency' => 'UAH',
                'payment_date' => now('Europe/Kiev')->toDateString(),
                'payment_time' => '13:15',
                'payment_type' => 'order',
                'payment_source' => 'direct',
                'order_public_id' => $order->public_id,
                'return_context' => 'order',
                'suggested_amount' => 1000,
                'suggested_amount_uah' => 1000,
            ])
            ->assertOk()
            ->assertJsonPath('automatic_overpayment_amount', 200);

        $orderPayment = ClientPayment::query()->where('order_id', $order->id)->sole();
        $automaticOverpayment = ClientPayment::query()->where('is_automatic', true)->sole();
        $this->assertSame(1000, $orderPayment->amount_uah);
        $this->assertSame(1200, $orderPayment->amount);
        $this->assertStringContainsString('Автоматично запропоновані значення', (string) $orderPayment->comment);
        $this->assertSame(200, $automaticOverpayment->amount_uah);
        $this->assertNull($automaticOverpayment->order_id);
        $this->assertSame($orderPayment->id, $automaticOverpayment->source_payment_id);
        $this->assertSame($automaticOverpayment->public_id, $response->json('automatic_overpayment_id'));

        $order->refresh();
        $this->assertSame('1000.00', $order->payments_total);
        $this->assertSame('0.00', $order->amount_due);
        $this->assertSame(1, $order->payments()->count());

        $clientPage = $this->actingAs($user)
            ->get(route('orders.clients.show', ['client' => $client, 'section' => 'payments']));
        $clientPage
            ->assertOk()
            ->assertSee('Переглянути')
            ->assertSee("isReadOnlyPayment ? 'Перегляд платежу'", false)
            ->assertSee('<fieldset :disabled="isReadOnlyPayment">', false)
            ->assertSee('x-show="!isReadOnlyPayment"', false)
            ->assertSee('unavailable: false', false)
            ->assertSee('restorePaymentOrderSelection(selectedOrderId)', false)
            ->assertSee("this.paymentForm.orderPublicId = '';", false)
            ->assertSee('this.paymentForm.orderPublicId = orderId;', false);
        $clientPage->assertViewHas('paymentModalData', fn ($payments): bool => $payments->contains(
            fn (array $payment): bool => $payment['id'] === $orderPayment->public_id
                && $payment['orderPublicId'] === $order->public_id
                && $payment['orderAmountDue'] === 1000
                && $payment['automaticOverpaymentId'] === $automaticOverpayment->public_id
        ));

        $this->actingAs($user)
            ->patchJson(route('orders.clients.payments.update', [$client, $automaticOverpayment]), [
                'amount' => 100,
            ])
            ->assertUnprocessable();
    }

    public function test_payment_from_client_overpayment_cannot_exceed_selected_order_amount_due(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'total_cost' => 500,
            'amount_due' => 500,
        ]);
        ClientPayment::query()->create([
            'client_id' => $client->id,
            'amount' => 2000,
            'amount_uah' => 2000,
            'currency' => 'UAH',
            'payment_type' => 'prepayment',
            'paid_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('orders.clients.payments.store', $client), [
                'amount' => 600,
                'amount_uah' => 600,
                'currency' => 'UAH',
                'payment_date' => now('Europe/Kiev')->toDateString(),
                'payment_time' => '13:30',
                'payment_type' => 'order',
                'payment_source' => 'overpayment',
                'order_public_id' => $order->public_id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->assertSame(0, $order->payments()->count());
    }

    public function test_order_payment_requires_an_order_owned_by_the_same_client(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $clientOrder = Order::factory()->create(['client_id' => $client->id, 'total_cost' => 1000]);
        $otherOrder = Order::factory()->create(['client_id' => $otherClient->id]);
        $payload = [
            'amount' => 500,
            'currency' => 'UAH',
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

        $this->assertSame(
            $clientOrder->id,
            ClientPayment::query()->whereNotNull('order_id')->sole()->order_id
        );

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
            ->assertJsonFragment(['id' => $orders[0]->public_id, 'number' => $orders[0]->order_number, 'amountDue' => 1000])
            ->assertJsonFragment(['id' => $orders[1]->public_id, 'number' => $orders[1]->order_number, 'amountDue' => 600])
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

    public function test_non_new_order_statuses_block_new_payments_and_are_excluded_from_selectors(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $newOrder = Order::factory()->create(['client_id' => $client->id, 'status' => Order::STATUS_NEW]);
        $blockedOrder = Order::factory()->create(['client_id' => $client->id, 'status' => Order::STATUS_BLOCKED]);
        $completedOrder = Order::factory()->create(['client_id' => $client->id, 'status' => Order::STATUS_COMPLETED]);
        $cancelledOrder = Order::factory()->create(['client_id' => $client->id, 'status' => Order::STATUS_CANCELLED]);

        $this->actingAs($user)
            ->getJson(route('orders.clients.payments.orders', $client))
            ->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonFragment(['id' => $newOrder->public_id])
            ->assertJsonMissing(['id' => $blockedOrder->public_id])
            ->assertJsonMissing(['id' => $completedOrder->public_id])
            ->assertJsonMissing(['id' => $cancelledOrder->public_id]);

        $paymentPayload = [
            'amount' => 100,
            'currency' => 'UAH',
            'payment_date' => now('Europe/Kiev')->toDateString(),
            'payment_time' => '10:30',
            'payment_type' => 'order',
            'payment_source' => 'direct',
            'return_context' => 'client',
        ];
        foreach ([$blockedOrder, $completedOrder, $cancelledOrder] as $order) {
            $this->actingAs($user)
                ->postJson(route('orders.clients.payments.store', $client), [
                    ...$paymentPayload,
                    'order_public_id' => $order->public_id,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('order_public_id');
        }

        $this->actingAs($user)
            ->get(route('orders.show', $blockedOrder))
            ->assertOk()
            ->assertSee('data-order-payments-blocked="true"', false)
            ->assertSee('paymentsBlocked: true', false)
            ->assertSee('Внесення платежів недоступне для заблокованих замовлень. Розблокуйте замовлення');

        $this->actingAs($user)
            ->get(route('orders.show', $cancelledOrder))
            ->assertOk()
            ->assertSee('data-order-payments-disabled="true"', false)
            ->assertSee("selectedStatus === 'cancelled'", false);
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
                'amount_uah' => 11625,
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
            ['Сума операції', 'Валюта', 'Курс SALE ПриватБанку', 'Тип курсу', 'Джерело курсу', 'Час отримання курсу', 'Автоматично розрахована сума (ГРН)', 'Еквівалент у ГРН', 'Дата та час', 'Тип платежу', 'Номер замовлення', 'Коментар'],
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

    public function test_payment_tables_mark_current_or_historical_comments_with_a_blue_check(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create();
        $order = Order::factory()->create(['client_id' => $client->id, 'total_cost' => 1000]);
        $paymentWithHistoricalComment = ClientPayment::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'amount' => 100,
            'currency' => 'UAH',
            'payment_type' => 'order',
            'paid_at' => now(),
            'comment' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $paymentWithHistoricalComment->histories()->create([
            'user_id' => $user->id,
            'changes' => [[
                'field' => 'comment',
                'label' => 'Коментар',
                'before' => 'Коментар, який був видалений',
                'after' => '—',
            ]],
            'created_at' => now(),
        ]);
        ClientPayment::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'amount' => 100,
            'currency' => 'UAH',
            'payment_type' => 'order',
            'paid_at' => now()->subMinute(),
            'comment' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->assertTrue($paymentWithHistoricalComment->fresh()->hasCommentTrace());

        $clientResponse = $this->actingAs($user)
            ->get(route('orders.clients.show', ['client' => $client, 'section' => 'payments']))
            ->assertOk()
            ->assertSeeInOrder(['Валюта', 'Коментар', 'Користувач'])
            ->assertSee('text-blue-600', false);
        $this->assertSame(1, substr_count($clientResponse->getContent(), 'data-payment-comment-marker'));

        $orderResponse = $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Історія платежів замовлення')
            ->assertSeeInOrder(['Валюта', 'Коментар', 'Користувач']);
        $this->assertSame(1, substr_count($orderResponse->getContent(), 'data-payment-comment-marker'));
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
