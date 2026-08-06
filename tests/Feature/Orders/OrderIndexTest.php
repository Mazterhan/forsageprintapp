<?php

namespace Tests\Feature\Orders;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
    }

    public function test_orders_page_displays_order_table_and_create_link(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $editor = User::factory()->create(['name' => 'Останній редактор']);
        $order = Order::factory()->create([
            'customer_name' => 'Тестовий замовник',
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
            ->assertSeeInOrder(['Номер замовлення', 'Оплата', "Ім'я замовника"])
            ->assertSee("Ім'я замовника")
            ->assertSee('До сплати')
            ->assertSee('Вартість')
            ->assertSee($order->order_number)
            ->assertSee('Тестовий замовник')
            ->assertSee('Останній редактор')
            ->assertSee('650.25')
            ->assertSee('1 000.50');
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
            ->assertSee('Статус оплати')
            ->assertSee('Вивантажити замовлення у PDF')
            ->assertSee('Платежі')
            ->assertSee('Номенклатура')
            ->assertSee('Кількість')
            ->assertSee('Вартість за одн.')
            ->assertSee('Сума з ПДВ')
            ->assertSee('Загальна сума сплат')
            ->assertSee('Сума до сплати')
            ->assertSee(route('orders.store'), false)
            ->assertSee('Створити замовлення')
            ->assertDontSee('Вартість загальна (грн)')
            ->assertSee('x-show="hasNomenclatureItem()"', false)
            ->assertSee('maxlength="500"', false)
            ->assertSee('@resize.window.debounce.100ms="resizeAllNomenclatureFields()"', false);

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
                    ['nomenclature' => 'Банер', 'quantity' => 2, 'unit_cost' => 350],
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
        $this->assertSame($order->public_id, $response->json('order_id'));

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee($client->name)
            ->assertSee('900');
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
            ->assertSee('Редактор замовлення')
            ->assertSee('Дата створення:')
            ->assertSee('Частково сплачено')
            ->assertSee('right-full', false)
            ->assertSee('Редагувати')
            ->assertSee(route('orders.edit', $order), false)
            ->assertSee('background-color: #D3D4D4', false)
            ->assertDontSee('Створити замовлення');

        $this->actingAs($user)
            ->get('/orders/'.$order->id)
            ->assertNotFound();
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
            ->assertSee('Автор зміни')
            ->assertSee('Редагування')
            ->assertSee('Номенклатура')
            ->assertSee('Старе значення')
            ->assertSee('Нове значення');
    }

    public function test_order_can_be_edited_in_place_and_item_changes_are_recorded(): void
    {
        $user = $this->createUserWithRole(['can_orders' => true]);
        $client = Client::query()->create([
            'code' => 'FP-000020',
            'name' => 'Замовник редагування',
            'status' => 'active',
        ]);
        $order = Order::factory()->create([
            'client_id' => $client->id,
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
            ->assertSee('Зберегти')
            ->assertSee(route('orders.update', $order), false);

        $this->actingAs($user)
            ->patchJson(route('orders.update', $order), [
                'client_id' => $client->id,
                'customer_name' => $client->name,
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
        $this->assertSame($user->id, $order->last_edited_by);
        $this->assertSame('Змінена позиція', $order->items[0]['nomenclature']);
        $this->assertNotEmpty($order->items[1]['item_id']);
        $this->assertTrue($order->histories()->where('operation_type', 'item_updated')->exists());
        $this->assertTrue($order->histories()->where('operation_type', 'item_created')->exists());

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
            ->assertSee('Створення')
            ->assertSee('Редагування')
            ->assertSee('Видалення')
            ->assertSee('Номенклатура: Нова позиція')
            ->assertSee('Кількість: 1')
            ->assertDontSee($newItem['item_id'])
            ->assertSee($user->name);
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
