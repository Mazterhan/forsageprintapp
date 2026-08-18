<?php

namespace Tests\Feature\Orders;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\CreatesRoles;
use Tests\TestCase;

class OrderIndexTest extends TestCase
{
    use CreatesRoles;
    use RefreshDatabase;

    public function test_order_number_is_generated_from_database_id(): void
    {
        $order = Order::factory()->create();

        $this->assertSame(sprintf('O-%06d', $order->id), $order->fresh()->order_number);
        $this->assertSame(Order::STATUS_NEW, $order->fresh()->status);
    }

    public function test_orders_page_displays_order_table_and_create_link(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $creator = User::factory()->create(['name' => 'Автор замовлення']);
        $editor = User::factory()->create(['name' => 'Останній редактор']);
        $order = Order::factory()->create([
            'customer_name' => 'Тестовий замовник',
            'created_by' => $creator->id,
            'last_edited_by' => $editor->id,
            'amount_due' => 650.25,
            'total_cost' => 1000.50,
        ]);

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Створити замовлення')
            ->assertSee(route('orders.create'), false)
            ->assertSee('Номер замовлення')
            ->assertSeeInOrder(['Дата', 'Статус', 'Номер замовлення'])
            ->assertSeeInOrder(['Номер замовлення', 'Оплата', "Ім'я замовника"])
            ->assertSee("Ім'я замовника")
            ->assertSee('До сплати')
            ->assertSee('Вартість')
            ->assertSee($order->order_number)
            ->assertSee('Тестовий замовник')
            ->assertSee('Автор замовлення')
            ->assertDontSee('Останній редактор')
            ->assertSee('650.25')
            ->assertSee('1 000.50');

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertDontSee('data-page-back-link', false);
    }

    public function test_user_can_choose_and_reorder_orders_table_columns(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Редагувати відображення таблиці')
            ->assertSee('Редагування відображення таблиці')
            ->assertSeeInOrder(['Доступно', 'Вибрано'])
            ->assertSee('Застосувати')
            ->assertSee('Скасувати')
            ->assertViewHas('orderTableColumns', [
                'date',
                'status',
                'number',
                'payment',
                'customer',
                'user',
                'amount_due',
                'total_cost',
            ]);

        $this->actingAs($user)
            ->patchJson(route('orders.table-columns.update'), [
                'columns' => ['number', 'date', 'total_cost'],
            ])
            ->assertOk()
            ->assertJsonPath('columns', ['number', 'date', 'total_cost']);

        $this->assertSame(
            ['number', 'date', 'total_cost'],
            $user->fresh()->orders_table_columns
        );

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertViewHas('orderTableColumns', ['number', 'date', 'total_cost'])
            ->assertSeeInOrder([
                'data-order-column="number"',
                'data-order-column="date"',
                'data-order-column="total_cost"',
            ], false)
            ->assertDontSee('data-order-column="payment"', false);

        $this->post(route('logout'))
            ->assertRedirect('/');
        $this->assertGuest();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->get(route('orders.index'))
            ->assertOk()
            ->assertViewHas('orderTableColumns', ['number', 'date', 'total_cost']);

        $otherUser = $this->createUserWithRole(['can_orders' => true]);
        $this->actingAs($otherUser)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertViewHas('orderTableColumns', [
                'date',
                'status',
                'number',
                'payment',
                'customer',
                'user',
                'amount_due',
                'total_cost',
            ]);

        $this->actingAs($user)
            ->patchJson(route('orders.table-columns.update'), ['columns' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('columns');
    }

    public function test_order_customer_names_link_to_client_card_when_client_access_is_available(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $client = Client::factory()->create(['name' => 'Клієнт з посиланням']);
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'customer_name' => $client->name,
            'created_by' => $user->id,
        ]);
        $clientUrl = route('orders.clients.show', $client);

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('href="'.$clientUrl.'"', false)
            ->assertSee($client->name);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('href="'.$clientUrl.'"', false)
            ->assertSee($client->name);
    }

    public function test_orders_table_displays_payment_statuses_with_order_colors(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $client = Client::factory()->create();
        $orders = collect([
            ['total' => 1000, 'payment' => 0],
            ['total' => 1000, 'payment' => 400],
            ['total' => 1000, 'payment' => 1000],
            ['total' => 1000, 'payment' => 1200],
        ])->map(fn (array $values): Order => Order::factory()->create([
            'client_id' => $client->id,
            'total_cost' => $values['total'],
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
            ->get(route('orders.index', ['per_page' => 'all']))
            ->assertOk()
            ->assertSee(route('orders.index', [
                'per_page' => 'all',
                'sort' => 'payment',
                'direction' => 'asc',
            ]))
            ->assertSee('Не сплачено')
            ->assertSee('border-red-300 bg-rose-100 text-red-800', false)
            ->assertSee('Частково сплачено')
            ->assertSee('border-orange-500 bg-yellow-100 text-orange-800', false)
            ->assertSee('Сплачено')
            ->assertSee('border-green-300 bg-green-100 text-green-800', false)
            ->assertSee('Є переплата')
            ->assertSee('border-blue-400 bg-teal-100 text-blue-800', false);

        $this->actingAs($user)
            ->get(route('orders.index', [
                'per_page' => 'all',
                'sort' => 'payment',
                'direction' => 'asc',
            ]))
            ->assertOk()
            ->assertSeeInOrder(['Не сплачено', 'Частково сплачено', 'Сплачено', 'Є переплата']);

        $this->actingAs($user)
            ->get(route('orders.index', [
                'per_page' => 'all',
                'sort' => 'payment',
                'direction' => 'desc',
            ]))
            ->assertOk()
            ->assertSeeInOrder(['Є переплата', 'Сплачено', 'Частково сплачено', 'Не сплачено']);
    }

    public function test_orders_table_can_be_filtered_by_actual_payment_client_user_and_order_status_values(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $firstEditor = User::factory()->create(['name' => 'Редактор Альфа']);
        $secondEditor = User::factory()->create(['name' => 'Редактор Бета']);
        $unusedEditor = User::factory()->create(['name' => 'Редактор без замовлень']);
        $firstClient = Client::factory()->create(['name' => 'Клієнт Альфа']);
        $secondClient = Client::factory()->create(['name' => 'Клієнт Бета']);
        $unusedClient = Client::factory()->create(['name' => 'Клієнт без замовлень']);

        $unpaidOrder = Order::factory()->create([
            'client_id' => $firstClient->id,
            'customer_name' => $firstClient->name,
            'last_edited_by' => $firstEditor->id,
            'created_by' => $firstEditor->id,
            'status' => Order::STATUS_NEW,
            'total_cost' => 1000,
        ]);
        $partialOrder = Order::factory()->create([
            'client_id' => $secondClient->id,
            'customer_name' => $secondClient->name,
            'last_edited_by' => $secondEditor->id,
            'created_by' => $secondEditor->id,
            'status' => Order::STATUS_BLOCKED,
            'total_cost' => 1000,
        ]);
        $paidOrder = Order::factory()->create([
            'client_id' => $firstClient->id,
            'customer_name' => $firstClient->name,
            'last_edited_by' => $firstEditor->id,
            'created_by' => $firstEditor->id,
            'status' => Order::STATUS_COMPLETED,
            'total_cost' => 1000,
        ]);

        foreach ([[$partialOrder, 400], [$paidOrder, 1000]] as [$order, $amount]) {
            ClientPayment::query()->create([
                'client_id' => $order->client_id,
                'order_id' => $order->id,
                'amount' => $amount,
                'currency' => 'UAH',
                'payment_type' => 'order',
                'paid_at' => now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('data-orders-filters', false)
            ->assertSee('data-orders-filter-details', false)
            ->assertSee('data-orders-filter-input', false)
            ->assertSee('data-orders-client-search', false)
            ->assertSee('data-orders-client-option', false)
            ->assertSee('window.setTimeout(submitFilters, 2000)', false)
            ->assertSeeInOrder(["Ім'я замовника", 'Статус замовлення', 'Оплата', 'Користувач'])
            ->assertSee('name="payment_status[]"', false)
            ->assertSee('name="client_id[]"', false)
            ->assertSee('name="user_id[]"', false)
            ->assertSee('name="order_status[]"', false)
            ->assertSee($firstClient->name)
            ->assertSee($secondClient->name)
            ->assertDontSee($unusedClient->name)
            ->assertSee($firstEditor->name)
            ->assertSee($secondEditor->name)
            ->assertDontSee($unusedEditor->name)
            ->assertDontSee('Є переплата');

        $this->actingAs($user)
            ->get(route('orders.index', [
                'payment_status' => ['partial'],
                'client_id' => [$secondClient->id],
                'user_id' => [$secondEditor->id],
                'order_status' => [Order::STATUS_BLOCKED],
                'per_page' => 'all',
            ]))
            ->assertOk()
            ->assertSee($partialOrder->order_number)
            ->assertDontSee($unpaidOrder->order_number)
            ->assertDontSee($paidOrder->order_number);

        $this->actingAs($user)
            ->get(route('orders.index', [
                'payment_status' => ['unpaid', 'paid'],
                'client_id' => [$firstClient->id],
                'per_page' => 'all',
            ]))
            ->assertOk()
            ->assertSee($unpaidOrder->order_number)
            ->assertSee($paidOrder->order_number)
            ->assertDontSee($partialOrder->order_number);
    }

    public function test_create_order_form_loads_active_clients_and_requires_orders_permission(): void
    {
        $allowedUser = $this->createUserWithRole(['can_orders' => true]);
        $forbiddenUser = $this->createUserWithRole(['can_orders' => false]);
        Client::query()->create([
            'code' => 'FP-000001',
            'name' => 'Активний замовник',
            'status' => 'active',
        ]);
        Client::query()->create([
            'code' => 'FP-000002',
            'name' => 'Неактивний замовник',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($allowedUser)
            ->get(route('orders.create'));

        $response
            ->assertOk()
            ->assertViewHas('clients', function ($clients): bool {
                return $clients->pluck('name')->all() === ['Активний замовник'];
            })
            ->assertSee('Створити замовлення для')
            ->assertSee('Оберіть замовника')
            ->assertDontSee('id="order-payment-status"', false)
            ->assertDontSee('Вивантажити замовлення у PDF')
            ->assertDontSee('>Платежі<', false)
            ->assertSee('Номенклатура')
            ->assertSee('Опис')
            ->assertSee('w-[220px] border-b border-r border-gray-200 p-2 align-middle', false)
            ->assertSee('Кількість')
            ->assertSee('Вартість за одн.')
            ->assertSee('Сума з ПДВ')
            ->assertSee('Загальна сума сплат')
            ->assertSee('Сума до сплати')
            ->assertSee(route('orders.store'), false)
            ->assertSee('Створити замовлення')
            ->assertSee('Замовлення буде створено з введеними даними. Підтверджуєте створення замовлення?')
            ->assertSee('Замовлення буде збережено з внесеними змінами. Підтверджуєте збереження змін?')
            ->assertSee('window.confirm(message)', false)
            ->assertSee('data-page-back-link', false)
            ->assertSee('href="'.route('orders.index').'"', false)
            ->assertSee('images/back.png', false)
            ->assertDontSee('data-order-edit-cancel', false)
            ->assertDontSee('Повернутись до замовлень')
            ->assertDontSee('Вартість загальна (грн)')
            ->assertSee('x-show="hasNomenclatureItem() && (isEdit || hasCustomerName())"', false)
            ->assertSee('Додати до існуючого замовлення')
            ->assertSee(str_replace('/', '\\/', route('orders.append-candidate')), false)
            ->assertSee('maxlength="500"', false)
            ->assertSee('data-order-description', false)
            ->assertSee('maxlength="200"', false)
            ->assertDontSee('placeholder="Введіть опис"', false)
            ->assertSee('Залишилось символів:', false)
            ->assertSee('Досягнуто максимальний ліміт: 200/200')
            ->assertSee("quantity: source.quantity ? String(source.quantity) : '1'", false)
            ->assertDontSee("|| String(item.quantity || '').trim() !== ''", false)
            ->assertSee('@resize.window.debounce.100ms="resizeAllItemTextFields()"', false);
        $this->assertSame(2, substr_count($response->getContent(), 'fixed inset-0 z-[12000] !mt-0'));

        $this->actingAs($forbiddenUser)
            ->get(route('orders.create'))
            ->assertForbidden();
    }

    public function test_existing_client_order_is_saved_with_guid_number_items_and_totals(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $client = Client::query()->create([
            'code' => 'FP-000010',
            'name' => 'Постійний замовник',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('orders.store'), [
                'client_id' => $client->id,
                'customer_name' => $client->name,
                'items' => [
                    ['nomenclature' => 'Банер', 'description' => 'Для фасаду', 'quantity' => 2, 'unit_cost' => 350],
                    ['nomenclature' => 'Монтаж', 'quantity' => 1, 'unit_cost' => 200],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect_url', route('orders.index'));

        $order = Order::query()->sole();

        $this->assertTrue(Str::isUuid($order->public_id));
        $this->assertSame(sprintf('O-%06d', $order->id), $order->order_number);
        $this->assertSame($client->id, $order->client_id);
        $this->assertSame($user->id, $order->last_edited_by);
        $this->assertSame('900.00', $order->total_cost);
        $this->assertSame('900.00', $order->amount_due);
        $this->assertSame(700, $order->items[0]['sum']);
        $this->assertSame('Для фасаду', $order->items[0]['description']);
        $this->assertSame($order->public_id, $response->json('order_id'));

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee($client->name)
            ->assertSee('900');

        $this->actingAs($user)
            ->postJson(route('orders.store'), [
                'client_id' => $client->id,
                'customer_name' => $client->name,
                'items' => [[
                    'nomenclature' => 'Позиція з надто довгим описом',
                    'description' => str_repeat('а', 201),
                    'quantity' => 1,
                    'unit_cost' => 100,
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.description');
    }

    public function test_new_order_positions_can_be_appended_to_the_newest_unpaid_new_order(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $client = Client::factory()->create();
        $olderOrder = Order::factory()->create([
            'client_id' => $client->id,
            'customer_name' => $client->name,
            'status' => Order::STATUS_NEW,
            'created_by' => $user->id,
            'items' => [],
            'total_cost' => 0,
            'amount_due' => 0,
            'created_at' => now()->subDay(),
        ]);
        $newestOrder = Order::factory()->create([
            'client_id' => $client->id,
            'customer_name' => $client->name,
            'status' => Order::STATUS_NEW,
            'created_by' => $user->id,
            'items' => [[
                'item_id' => (string) Str::uuid(),
                'nomenclature' => 'Існуюча позиція',
                'description' => '',
                'quantity' => 1,
                'unit_cost' => 100,
                'sum' => 100,
            ]],
            'total_cost' => 100,
            'amount_due' => 100,
            'created_at' => now(),
        ]);
        Order::factory()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_COMPLETED,
            'created_at' => now()->addMinute(),
        ]);

        $this->actingAs($user)
            ->getJson(route('orders.append-candidate', ['client_id' => $client->id]))
            ->assertOk()
            ->assertJsonPath('order.id', $newestOrder->public_id)
            ->assertJsonPath('order.number', $newestOrder->order_number)
            ->assertJsonPath('order.append_url', route('orders.append-items', $newestOrder));

        $this->actingAs($user)
            ->postJson(route('orders.append-items', $newestOrder), [
                'items' => [[
                    'nomenclature' => 'Нова позиція',
                    'description' => 'Додано з форми створення',
                    'quantity' => 2,
                    'unit_cost' => 300,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('orders.show', $newestOrder));

        $newestOrder->refresh();
        $this->assertCount(2, $newestOrder->items);
        $this->assertSame('Існуюча позиція', $newestOrder->items[0]['nomenclature']);
        $this->assertSame('Нова позиція', $newestOrder->items[1]['nomenclature']);
        $this->assertSame(700.0, (float) $newestOrder->total_cost);
        $this->assertSame(700.0, (float) $newestOrder->amount_due);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $newestOrder->id,
            'operation_type' => 'item_created',
            'description' => 'Додано позицію номенклатури',
        ]);
        $this->assertCount(0, $olderOrder->fresh()->items);
    }

    public function test_positions_cannot_be_appended_when_order_is_no_longer_new_and_unpaid(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $client = Client::factory()->create();
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_NEW,
            'created_by' => $user->id,
            'total_cost' => 1000,
        ]);
        ClientPayment::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'amount' => 1,
            'amount_uah' => 1,
            'currency' => 'UAH',
            'payment_type' => 'order',
            'paid_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->getJson(route('orders.append-candidate', ['client_id' => $client->id]))
            ->assertOk()
            ->assertJsonPath('order', null);

        $this->actingAs($user)
            ->postJson(route('orders.append-items', $order), [
                'items' => [[
                    'nomenclature' => 'Не повинна додатися',
                    'quantity' => 1,
                    'unit_cost' => 100,
                ]],
            ])
            ->assertConflict();
    }

    public function test_unpaid_new_orders_of_one_client_can_be_merged_with_full_history(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_update' => true,
        ]);
        $client = Client::factory()->create();
        $source = Order::factory()->create([
            'client_id' => $client->id,
            'customer_name' => $client->name,
            'status' => Order::STATUS_NEW,
            'created_by' => $user->id,
            'items' => [[
                'item_id' => (string) Str::uuid(),
                'nomenclature' => 'Позиція джерела',
                'description' => '',
                'quantity' => 2,
                'unit_cost' => 200,
                'sum' => 400,
            ]],
            'total_cost' => 400,
            'amount_due' => 400,
        ]);
        $target = Order::factory()->create([
            'client_id' => $client->id,
            'customer_name' => $client->name,
            'status' => Order::STATUS_NEW,
            'created_by' => $user->id,
            'items' => [[
                'item_id' => (string) Str::uuid(),
                'nomenclature' => 'Позиція отримувача',
                'description' => '',
                'quantity' => 1,
                'unit_cost' => 100,
                'sum' => 100,
            ]],
            'total_cost' => 100,
            'amount_due' => 100,
        ]);
        $completed = Order::factory()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_COMPLETED,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $source))
            ->assertOk()
            ->assertSee('Опції')
            ->assertSee("Об'єднати", false)
            ->assertSee('data-order-merge-option', false)
            ->assertSee(route('orders.edit', $source), false)
            ->assertSee(str_replace('/', '\\/', route('orders.merge-candidates', $source)), false)
            ->assertSee(str_replace('/', '\\/', route('orders.merge', $source)), false);

        $this->actingAs($user)
            ->getJson(route('orders.merge-candidates', $source))
            ->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.id', $target->public_id)
            ->assertJsonPath('orders.0.number', $target->order_number)
            ->assertJsonMissing(['id' => $completed->public_id]);

        $this->actingAs($user)
            ->postJson(route('orders.merge', $source), [
                'target_order_id' => $target->public_id,
            ])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('orders.show', $target));

        $source->refresh();
        $target->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $source->status);
        $this->assertSame([], $source->items);
        $this->assertSame(0.0, (float) $source->total_cost);
        $this->assertCount(2, $target->items);
        $this->assertSame('Позиція отримувача', $target->items[0]['nomenclature']);
        $this->assertSame('Позиція джерела', $target->items[1]['nomenclature']);
        $this->assertSame(500.0, (float) $target->total_cost);
        $this->assertTrue($source->histories()->where('operation_type', 'item_deleted')->exists());
        $this->assertTrue($source->histories()->where('field_name', 'status')->exists());
        $this->assertTrue($target->histories()->where('operation_type', 'item_created')->exists());

        $this->actingAs($user)
            ->get(route('orders.show', $source))
            ->assertOk()
            ->assertSee('Скасовано')
            ->assertDontSee('data-order-merge-option', false);
    }

    public function test_order_client_is_locked_in_edit_form_and_cannot_be_changed_by_request(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_update' => true,
        ]);
        $originalClient = Client::factory()->create(['name' => 'Початковий клієнт']);
        $otherClient = Client::factory()->create(['name' => 'Інший клієнт']);
        $order = Order::factory()->create([
            'client_id' => $originalClient->id,
            'customer_name' => $originalClient->name,
            'created_by' => $user->id,
            'items' => [[
                'item_id' => (string) Str::uuid(),
                'nomenclature' => 'Позиція',
                'description' => '',
                'quantity' => 1,
                'unit_cost' => 100,
                'sum' => 100,
            ]],
            'total_cost' => 100,
            'amount_due' => 100,
        ]);

        $this->actingAs($user)
            ->get(route('orders.edit', $order))
            ->assertOk()
            ->assertSee('id="order-customer-name"', false)
            ->assertSee('readonly', false)
            ->assertDontSee('aria-label="Відкрити список замовників"', false);

        $this->actingAs($user)
            ->patchJson(route('orders.update', $order), [
                'client_id' => $otherClient->id,
                'customer_name' => $otherClient->name,
                'items' => [[
                    'item_id' => $order->items[0]['item_id'],
                    'nomenclature' => 'Позиція',
                    'quantity' => 1,
                    'unit_cost' => 100,
                ]],
            ])
            ->assertOk();

        $order->refresh();
        $this->assertSame($originalClient->id, $order->client_id);
        $this->assertSame($originalClient->name, $order->customer_name);
    }

    public function test_unknown_client_requires_confirmation_then_is_created_with_order(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $payload = [
            'customer_name' => 'Новий замовник',
            'items' => [
                ['nomenclature' => 'Наліпки', 'quantity' => 3, 'unit_cost' => 100],
            ],
        ];

        $this->actingAs($user)
            ->postJson(route('orders.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonPath('code', 'client_not_found');

        $this->assertDatabaseMissing('clients', ['name' => 'Новий замовник']);
        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($user)
            ->postJson(route('orders.store'), array_merge($payload, ['create_client' => true]))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $client = Client::query()->where('name', 'Новий замовник')->sole();
        $order = Order::query()->sole();

        $this->assertSame('FP-'.str_pad((string) $client->id, 6, '0', STR_PAD_LEFT), $client->code);
        $this->assertSame($client->id, $order->client_id);
        $this->assertSame('300.00', $order->total_cost);
    }

    public function test_order_number_links_to_read_only_form_by_public_id(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $editor = User::factory()->create(['name' => 'Редактор замовлення']);
        $client = Client::factory()->create(['name' => 'Замовник для перегляду']);
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'customer_name' => 'Замовник для перегляду',
            'created_by' => $editor->id,
            'last_edited_by' => $editor->id,
            'items' => [
                [
                    'nomenclature' => 'Тестова номенклатура',
                    'quantity' => 2,
                    'unit_cost' => 400,
                    'sum' => 800,
                ],
            ],
            'payments_total' => 300,
            'amount_due' => 500,
            'total_cost' => 800,
        ]);
        ClientPayment::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'amount' => 300,
            'currency' => 'UAH',
            'payment_type' => 'order',
            'paid_at' => now(),
            'created_by' => $editor->id,
            'updated_by' => $editor->id,
        ]);

        $showUrl = route('orders.show', $order);
        $this->assertStringContainsString($order->public_id, $showUrl);
        $this->assertSame($order->public_id, basename((string) parse_url($showUrl, PHP_URL_PATH)));
        $this->assertNotSame((string) $order->id, basename((string) parse_url($showUrl, PHP_URL_PATH)));

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($showUrl, false);

        $this->actingAs($user)
            ->get($showUrl)
            ->assertOk()
            ->assertSee('Замовлення '.$order->order_number)
            ->assertSee('Замовник для перегляду')
            ->assertSee('Тестова номенклатура')
            ->assertSee('border align-middle whitespace-pre-wrap break-words', false)
            ->assertSee('Редактор замовлення')
            ->assertSee('Дата створення:')
            ->assertSee('Нове')
            ->assertSee('Частково сплачено')
            ->assertSee('data-page-back-link', false)
            ->assertSee('data-order-heading-inline', false)
            ->assertSeeInOrder(['Замовлення '.$order->order_number, 'Нове', 'Частково сплачено'])
            ->assertDontSee('right-full', false)
            ->assertSee('href="'.route('orders.index').'"', false)
            ->assertSee('Редагувати')
            ->assertSee(route('orders.edit', $order), false)
            ->assertSee('background-color: #D3D4D4', false)
            ->assertDontSee('Створити замовлення');

        $this->actingAs($user)
            ->get('/orders/'.$order->id)
            ->assertNotFound();
    }

    public function test_order_status_can_be_changed_separately_and_is_recorded_in_history(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_update' => true,
        ]);
        $order = Order::factory()->create([
            'status' => Order::STATUS_NEW,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('orderStatusEditor(', false)
            ->assertSee(str_replace('/', '\\/', route('orders.status.update', $order)), false)
            ->assertSee('data-order-status-selector', false)
            ->assertSee('chooseStatus(', false)
            ->assertDontSee('Змінити статус')
            ->assertSee('warnAboutUnsavedStatus($event)', false)
            ->assertSeeInOrder(['Замовлення '.$order->order_number, 'Статус замовлення', 'Нове', 'Не сплачено']);

        $this->actingAs($user)
            ->patchJson(route('orders.status.update', $order), [
                'status' => Order::STATUS_BLOCKED,
            ])
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_BLOCKED)
            ->assertJsonPath('status_label', 'Заблоковано');

        $this->assertSame(Order::STATUS_BLOCKED, $order->fresh()->status);
        $history = $order->histories()->where('field_name', 'status')->sole();
        $this->assertSame('order_updated', $history->operation_type);
        $this->assertSame('Статус замовлення', $history->description);
        $this->assertSame(['value' => 'Нове'], $history->before_value);
        $this->assertSame(['value' => 'Заблоковано'], $history->after_value);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee(":disabled=\"selectedStatus === 'blocked' || selectedStatus === 'completed'\"", false)
            ->assertSee('Редагування недоступне для заблокованого або виконаного замовлення');

        $this->actingAs($user)
            ->get(route('orders.edit', $order))
            ->assertStatus(409);

        $this->actingAs($user)
            ->patchJson(route('orders.update', $order), [])
            ->assertStatus(409);

        $this->actingAs($user)
            ->patchJson(route('orders.status.update', $order), ['status' => 'unknown'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->actingAs($user)
            ->patchJson(route('orders.status.update', $order), ['status' => Order::STATUS_COMPLETED])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_existing_completed_order_is_preserved_but_cannot_be_selected_or_edited(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_update' => true,
        ]);
        $order = Order::factory()->create([
            'status' => Order::STATUS_COMPLETED,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee("initialStatus: 'completed'", false)
            ->assertDontSee('data-order-status-option="completed"', false)
            ->assertSee(":disabled=\"selectedStatus === 'blocked' || selectedStatus === 'completed'\"", false);

        $this->actingAs($user)
            ->get(route('orders.edit', $order))
            ->assertStatus(409);

        $this->actingAs($user)
            ->patchJson(route('orders.update', $order), [])
            ->assertStatus(409);

        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);
    }

    public function test_edited_order_displays_change_history_without_autosave_label(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $editor = User::factory()->create(['name' => 'Автор зміни']);
        $order = Order::factory()->create([
            'last_edited_by' => $editor->id,
            'items' => [[
                'item_id' => 'stable-item-id',
                'nomenclature' => 'Старе значення',
                'quantity' => 1,
                'unit_cost' => 100,
                'sum' => 100,
            ]],
        ]);

        $this->actingAs($editor);
        $order->update([
            'items' => [[
                'item_id' => 'stable-item-id',
                'nomenclature' => 'Нове значення',
                'quantity' => 1,
                'unit_cost' => 100,
                'sum' => 100,
            ]],
        ]);

        $order->histories()->firstOrFail();

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('Час останнього автоматичного збереження:')
            ->assertSee('Історія змін замовлення')
            ->assertSee('historyUrl:', false)
            ->assertSee('toggleOrderHistory()', false)
            ->assertDontSee('Старе значення')
            ->assertViewHas('order', fn (Order $loadedOrder): bool => ! $loadedOrder->relationLoaded('histories'));

        $historyResponse = $this->actingAs($user)
            ->getJson(route('orders.history', $order))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('count', 1);
        $historyHtml = (string) $historyResponse->json('html');
        $this->assertStringContainsString('Автор зміни', $historyHtml);
        $this->assertStringContainsString('Редагування', $historyHtml);
        $this->assertStringContainsString('Номенклатура', $historyHtml);
        $this->assertStringContainsString('Старе значення', $historyHtml);
        $this->assertStringContainsString('Нове значення', $historyHtml);
    }

    public function test_order_pdf_is_downloaded_with_order_items_and_payment_totals(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $client = Client::factory()->create(['name' => 'Замовник PDF']);
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'customer_name' => $client->name,
            'items' => [[
                'nomenclature' => 'Друкована продукція для PDF',
                'description' => 'Опис позиції PDF',
                'quantity' => 2,
                'unit_cost' => 400,
                'sum' => 800,
            ]],
            'total_cost' => 800,
            'payments_total' => 300,
            'amount_due' => 500,
        ]);
        ClientPayment::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'amount' => 300,
            'amount_uah' => 300,
            'currency' => 'UAH',
            'payment_type' => 'order',
            'paid_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $pdfUrl = route('orders.pdf', $order);
        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($pdfUrl, false)
            ->assertSee('download', false)
            ->assertSee(asset('images/pdf-file-icon.png'), false)
            ->assertSee('Завантажити')
            ->assertSee('Вивантажити замовлення у PDF');

        $this->assertFileExists(public_path('images/pdf-file-icon.png'));

        $response = $this->actingAs($user)->get($pdfUrl);
        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('Замовлення-'.$order->order_number.'.pdf');
        $this->assertStringContainsString(
            "filename*=UTF-8''".rawurlencode('Замовлення-'.$order->order_number.'.pdf'),
            (string) $response->headers->get('content-disposition')
        );

        $this->assertStringStartsWith('%PDF-', $response->getContent());

        $pdfHtml = view('orders.pdf', [
            'order' => $order->load('client:id,name'),
            'items' => $order->items,
            'paymentsTotal' => 300,
            'amountDue' => 500,
        ])->render();
        $this->assertStringContainsString('Форсаж Прінт', $pdfHtml);
        $this->assertStringContainsString('Замовник PDF', $pdfHtml);
        $this->assertStringContainsString('Друкована продукція для PDF', $pdfHtml);
        $this->assertStringContainsString('Опис позиції PDF', $pdfHtml);
        $this->assertStringContainsString('Загальна сума сплат', $pdfHtml);
        $this->assertStringContainsString('500 грн', $pdfHtml);
        $this->assertStringContainsString('background: #e5e7eb;', $pdfHtml);
        $this->assertStringContainsString('border-bottom: 2px solid #000000;', $pdfHtml);
    }

    public function test_order_excel_is_downloaded_with_order_items_and_payment_totals(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $client = Client::factory()->create(['name' => 'Замовник Excel']);
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_BLOCKED,
            'customer_name' => $client->name,
            'items' => [[
                'nomenclature' => 'Друкована продукція для Excel',
                'description' => 'Опис позиції Excel',
                'quantity' => 2,
                'unit_cost' => 400,
                'sum' => 800,
            ]],
            'total_cost' => 800,
            'payments_total' => 300,
            'amount_due' => 500,
        ]);
        ClientPayment::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'amount' => 300,
            'amount_uah' => 300,
            'currency' => 'UAH',
            'payment_type' => 'order',
            'paid_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $excelUrl = route('orders.excel', $order);
        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSeeInOrder([$excelUrl, route('orders.pdf', $order)], false)
            ->assertSee(asset('images/excel-file-icon.png'), false)
            ->assertSee('Вивантажити замовлення в Excel');

        $this->assertFileExists(public_path('images/excel-file-icon.png'));

        $response = $this->actingAs($user)->get($excelUrl);
        $response
            ->assertOk()
            ->assertStreamed()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertDownload('Замовлення-'.$order->order_number.'.xlsx');
        $this->assertStringContainsString(
            "filename*=UTF-8''".rawurlencode('Замовлення-'.$order->order_number.'.xlsx'),
            (string) $response->headers->get('content-disposition')
        );

        $temporaryPath = tempnam(sys_get_temp_dir(), 'order-excel-');
        $this->assertNotFalse($temporaryPath);
        file_put_contents($temporaryPath, $response->streamedContent());

        try {
            $spreadsheet = IOFactory::load($temporaryPath);
            $sheet = $spreadsheet->getActiveSheet();

            $this->assertSame('Замовлення '.$order->order_number, $sheet->getCell('A1')->getValue());
            $this->assertSame('Форсаж Прінт', $sheet->getCell('B3')->getValue());
            $this->assertSame('Замовник Excel', $sheet->getCell('B4')->getValue());
            $this->assertSame('Друкована продукція для Excel', $sheet->getCell('B8')->getValue());
            $this->assertSame('Опис позиції Excel', $sheet->getCell('C8')->getValue());
            $this->assertSame(2, $sheet->getCell('D8')->getValue());
            $this->assertSame(400, $sheet->getCell('E8')->getValue());
            $this->assertSame(800, $sheet->getCell('F8')->getValue());
            $this->assertSame('Сума з ПДВ', $sheet->getCell('E10')->getValue());
            $this->assertSame(800, $sheet->getCell('F10')->getValue());
            $this->assertSame('Загальна сума сплат', $sheet->getCell('E11')->getValue());
            $this->assertSame(300, $sheet->getCell('F11')->getValue());
            $this->assertSame('Сума до сплати', $sheet->getCell('E12')->getValue());
            $this->assertSame(500, $sheet->getCell('F12')->getValue());

            $spreadsheet->disconnectWorksheets();
        } finally {
            unlink($temporaryPath);
        }
    }

    public function test_order_history_row_fill_alternates_by_displayed_change_time(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $order = Order::factory()->create();
        $historyRows = [
            ['Перша зміна цього часу', now('Europe/Kiev')->setDate(2026, 8, 7)->setTime(17, 47, 10)->utc()],
            ['Друга зміна цього часу', now('Europe/Kiev')->setDate(2026, 8, 7)->setTime(17, 47, 50)->utc()],
            ['Зміна другої групи', now('Europe/Kiev')->setDate(2026, 8, 6)->setTime(21, 22)->utc()],
            ['Зміна третьої групи', now('Europe/Kiev')->setDate(2026, 8, 6)->setTime(16, 3)->utc()],
        ];

        foreach ($historyRows as [$description, $createdAt]) {
            $order->histories()->create([
                'user_id' => $user->id,
                'operation_type' => 'item_updated',
                'description' => $description,
                'before_value' => ['value' => 'Було'],
                'after_value' => ['value' => 'Стало'],
                'created_at' => $createdAt,
            ]);
        }

        $response = $this->actingAs($user)
            ->getJson(route('orders.history', $order))
            ->assertOk();
        $historyHtml = (string) $response->json('html');

        $this->assertSame(2, preg_match_all('/data-history-time="07\.08\.2026 17:47"\s+data-history-stripe="0"/', $historyHtml));
        $this->assertSame(1, preg_match_all('/data-history-time="06\.08\.2026 21:22"\s+data-history-stripe="1"/', $historyHtml));
        $this->assertSame(1, preg_match_all('/data-history-time="06\.08\.2026 16:03"\s+data-history-stripe="0"/', $historyHtml));
    }

    public function test_order_can_be_edited_in_place_and_item_changes_are_recorded(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $creator = User::factory()->create(['name' => 'Незмінний автор замовлення']);
        $client = Client::query()->create([
            'code' => 'FP-000020',
            'name' => 'Замовник редагування',
            'status' => 'active',
        ]);
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'created_by' => $creator->id,
            'status' => Order::STATUS_NEW,
            'customer_name' => $client->name,
            'last_edited_by' => $user->id,
            'items' => [[
                'item_id' => 'existing-item',
                'nomenclature' => 'Стара позиція',
                'quantity' => 1,
                'unit_cost' => 100,
                'sum' => 100,
            ]],
            'payments_total' => 20,
            'amount_due' => 80,
            'total_cost' => 100,
        ]);
        ClientPayment::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'amount' => 20,
            'currency' => 'UAH',
            'payment_type' => 'order',
            'paid_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('orders.edit', $order))
            ->assertOk()
            ->assertSee('Редагування замовлення '.$order->order_number)
            ->assertSee("initialClientId: {$client->id}", false)
            ->assertSee("saveMethod: 'PATCH'", false)
            ->assertSee('initialOrderStatus:', false)
            ->assertSee("initialOrderStatus: 'new'", false)
            ->assertSee('x-model="orderStatus"', false)
            ->assertSee('warnAboutUnsavedStatus($event)', false)
            ->assertSee('status: this.orderStatus', false)
            ->assertSee('Зберегти')
            ->assertSee('data-order-edit-cancel', false)
            ->assertSeeInOrder(['data-order-edit-cancel', 'Скасувати', 'requestSaveOrder()', "isEdit ? 'Зберегти'"])
            ->assertDontSee('Повернутись до замовлення')
            ->assertSee(route('orders.show', $order), false)
            ->assertSee(route('orders.update', $order), false);

        $this->actingAs($user)
            ->patchJson(route('orders.update', $order), [
                'client_id' => $client->id,
                'created_by' => $user->id,
                'customer_name' => $client->name,
                'status' => Order::STATUS_CANCELLED,
                'items' => [
                    [
                        'item_id' => 'existing-item',
                        'nomenclature' => 'Змінена позиція',
                        'quantity' => 2,
                        'unit_cost' => 150,
                    ],
                    [
                        'nomenclature' => 'Нова позиція',
                        'quantity' => 1,
                        'unit_cost' => 200,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect_url', route('orders.show', $order));

        $order->refresh();
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame('500.00', $order->total_cost);
        $this->assertSame('480.00', $order->amount_due);
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame($creator->id, $order->created_by);
        $this->assertSame($user->id, $order->last_edited_by);
        $this->assertSame('Змінена позиція', $order->items[0]['nomenclature']);
        $this->assertNotEmpty($order->items[1]['item_id']);
        $this->assertTrue($order->histories()->where('operation_type', 'item_updated')->exists());
        $this->assertTrue($order->histories()->where('operation_type', 'item_created')->exists());
        $statusHistory = $order->histories()->where('field_name', 'status')->sole();
        $this->assertSame(['value' => 'Нове'], $statusHistory->before_value);
        $this->assertSame(['value' => 'Скасовано'], $statusHistory->after_value);

        $newItem = $order->items[1];
        $this->actingAs($user)
            ->patchJson(route('orders.update', $order), [
                'client_id' => $client->id,
                'customer_name' => $client->name,
                'items' => [[
                    'item_id' => $newItem['item_id'],
                    'nomenclature' => $newItem['nomenclature'],
                    'quantity' => $newItem['quantity'],
                    'unit_cost' => $newItem['unit_cost'],
                ]],
            ])
            ->assertOk();

        $this->assertTrue($order->histories()->where('operation_type', 'item_deleted')->exists());

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Незмінний автор замовлення')
            ->assertSee('Історія змін замовлення')
            ->assertDontSee('Видалення')
            ->assertViewHas('order', fn (Order $loadedOrder): bool => ! $loadedOrder->relationLoaded('histories'));

        $historyResponse = $this->actingAs($user)
            ->getJson(route('orders.history', $order))
            ->assertOk()
            ->assertJsonPath('ok', true);
        $historyHtml = (string) $historyResponse->json('html');
        $this->assertStringContainsString('Створення', $historyHtml);
        $this->assertStringContainsString('Редагування', $historyHtml);
        $this->assertStringContainsString('Видалення', $historyHtml);
        $this->assertStringContainsString('Номенклатура: Нова позиція', $historyHtml);
        $this->assertStringContainsString('Кількість: 1', $historyHtml);
        $this->assertStringNotContainsString($newItem['item_id'], $historyHtml);
        $this->assertStringContainsString(e($user->name), $historyHtml);
        $this->assertStringContainsString('bg-white', $historyHtml);
    }

    public function test_editing_legacy_items_without_ids_is_recorded_as_an_update_only(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $client = Client::query()->create([
            'code' => 'FP-000021',
            'name' => 'Замовник старого замовлення',
            'status' => 'active',
        ]);
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'customer_name' => $client->name,
            'last_edited_by' => $user->id,
            'items' => [
                [
                    'nomenclature' => 'Позиція без змін',
                    'quantity' => 1,
                    'unit_cost' => 100,
                    'sum' => 100,
                ],
                [
                    'nomenclature' => 'Стара назва',
                    'quantity' => 2,
                    'unit_cost' => 200,
                    'sum' => 400,
                ],
            ],
            'amount_due' => 500,
            'total_cost' => 500,
        ]);

        $this->actingAs($user)
            ->get(route('orders.edit', $order))
            ->assertOk();

        $order->refresh();
        $this->assertNotEmpty($order->items[0]['item_id']);
        $this->assertNotEmpty($order->items[1]['item_id']);
        $this->assertDatabaseCount('order_histories', 0);

        $this->actingAs($user)
            ->patchJson(route('orders.update', $order), [
                'client_id' => $client->id,
                'customer_name' => $client->name,
                'items' => [
                    [
                        'item_id' => $order->items[0]['item_id'],
                        'nomenclature' => 'Позиція без змін',
                        'quantity' => 1,
                        'unit_cost' => 100,
                    ],
                    [
                        'item_id' => $order->items[1]['item_id'],
                        'nomenclature' => 'Нова назва',
                        'quantity' => 2,
                        'unit_cost' => 200,
                    ],
                ],
            ])
            ->assertOk();

        $histories = $order->histories()->get();
        $this->assertCount(1, $histories);
        $this->assertSame('item_updated', $histories->first()->operation_type);
        $this->assertSame(2, $histories->first()->item_index);
        $this->assertSame('nomenclature', $histories->first()->field_name);
        $this->assertSame(['value' => 'Стара назва'], $histories->first()->before_value);
        $this->assertSame(['value' => 'Нова назва'], $histories->first()->after_value);
    }

    public function test_one_save_distinguishes_deleted_created_and_edited_items(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $client = Client::query()->create([
            'code' => 'FP-000022',
            'name' => 'Замовник комбінованої зміни',
            'status' => 'active',
        ]);
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'customer_name' => $client->name,
            'last_edited_by' => $user->id,
            'items' => [
                ['item_id' => 'item-to-delete', 'nomenclature' => 'Видалити', 'quantity' => 1, 'unit_cost' => 100, 'sum' => 100],
                ['item_id' => 'item-to-edit', 'nomenclature' => 'Редагувати', 'quantity' => 2, 'unit_cost' => 200, 'sum' => 400],
                ['item_id' => 'item-unchanged', 'nomenclature' => 'Без змін', 'quantity' => 3, 'unit_cost' => 300, 'sum' => 900],
            ],
            'amount_due' => 1400,
            'total_cost' => 1400,
        ]);

        $this->actingAs($user)
            ->patchJson(route('orders.update', $order), [
                'client_id' => $client->id,
                'customer_name' => $client->name,
                'items' => [
                    ['item_id' => 'item-to-edit', 'nomenclature' => 'Відредаговано', 'quantity' => 2, 'unit_cost' => 200],
                    ['item_id' => 'item-unchanged', 'nomenclature' => 'Без змін', 'quantity' => 3, 'unit_cost' => 300],
                    ['nomenclature' => 'Створено', 'quantity' => 4, 'unit_cost' => 400],
                ],
            ])
            ->assertOk();

        $this->assertSame(1, $order->histories()->where('operation_type', 'item_deleted')->count());
        $this->assertSame(1, $order->histories()->where('operation_type', 'item_created')->count());
        $this->assertSame(1, $order->histories()->where('operation_type', 'item_updated')->count());

        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'operation_type' => 'item_deleted',
            'item_index' => 1,
        ]);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'operation_type' => 'item_updated',
            'field_name' => 'nomenclature',
        ]);
        $this->assertDatabaseHas('order_histories', [
            'order_id' => $order->id,
            'operation_type' => 'item_created',
            'item_index' => 3,
        ]);
    }
}
