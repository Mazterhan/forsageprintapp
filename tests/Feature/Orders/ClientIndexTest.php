<?php

namespace Tests\Feature\Orders;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesRoles;
use Tests\TestCase;

class ClientIndexTest extends TestCase
{
    use CreatesRoles;
    use RefreshDatabase;

    public function test_clients_table_matches_orders_style_and_sorts_requested_columns(): void
    {
        $user = $this->createUserWithRole([
            'can_orders' => true,
            'orders_clients_manage' => true,
        ]);
        $managerZ = User::factory()->create(['name' => 'Менеджер Я', 'is_active' => true]);
        $managerA = User::factory()->create(['name' => 'Менеджер А', 'is_active' => true]);
        $managerM = User::factory()->create(['name' => 'Менеджер М', 'is_active' => true]);

        $alphaClient = Client::factory()->create([
            'name' => 'Клієнт Альфа',
            'category' => 'C',
            'is_vip' => true,
            'manager_id' => $managerZ->id,
            'status' => 'blocked',
        ]);
        $betaClient = Client::factory()->create([
            'name' => 'Клієнт Бета',
            'category' => 'A',
            'is_vip' => false,
            'manager_id' => $managerA->id,
            'status' => 'paused',
        ]);
        Client::factory()->create([
            'name' => 'Клієнт Гамма',
            'category' => 'B',
            'is_vip' => true,
            'manager_id' => $managerM->id,
            'status' => 'active',
        ]);

        $alphaOrders = collect(range(1, 4))->map(fn (): Order => Order::factory()->create([
            'client_id' => $alphaClient->id,
            'customer_name' => $alphaClient->name,
            'total_cost' => 1000,
        ]));
        foreach ([1 => 400, 2 => 1000, 3 => 1200] as $orderIndex => $amount) {
            ClientPayment::query()->create([
                'client_id' => $alphaClient->id,
                'order_id' => $alphaOrders[$orderIndex]->id,
                'amount' => $amount,
                'currency' => 'UAH',
                'payment_type' => 'order',
                'paid_at' => now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }
        $otherClientOrder = Order::factory()->create([
            'client_id' => $betaClient->id,
            'customer_name' => $betaClient->name,
            'total_cost' => 500,
        ]);

        $response = $this->actingAs($user)
            ->get(route('orders.clients.index', ['search' => 'Клієнт']))
            ->assertOk()
            ->assertSee("Ім'я / Назва")
            ->assertSee('clients-table', false)
            ->assertSee('background-color: #FCEEDF', false)
            ->assertSee('client-row row-alt', false)
            ->assertSee('background-color: #D8F1F2', false)
            ->assertSee(route('orders.clients.show', $alphaClient), false)
            ->assertSeeInOrder(["Ім'я / Назва", 'Кількість замовлень', 'Статус оплати замовлення', 'Категорія'])
            ->assertSeeInOrder(['Клієнт Альфа', '4', 'Відсутня: 1', 'Часткова: 1', 'Повна: 1', 'Переплата: 1'])
            ->assertDontSee('Відсутня: 0')
            ->assertDontSee('Часткова: 0')
            ->assertDontSee('Повна: 0')
            ->assertDontSee('Переплата: 0')
            ->assertDontSee('Дії');

        foreach (['name', 'orders_count', 'category', 'vip', 'manager', 'status'] as $column) {
            $response->assertSee(route('orders.clients.index', [
                'search' => 'Клієнт',
                'sort' => $column,
                'direction' => $column === 'name' ? 'desc' : 'asc',
            ]));
        }

        $expectedOrders = [
            'name' => ['Клієнт Альфа', 'Клієнт Бета', 'Клієнт Гамма'],
            'orders_count' => ['Клієнт Гамма', 'Клієнт Бета', 'Клієнт Альфа'],
            'category' => ['Клієнт Бета', 'Клієнт Гамма', 'Клієнт Альфа'],
            'vip' => ['Клієнт Бета', 'Клієнт Альфа', 'Клієнт Гамма'],
            'manager' => ['Клієнт Бета', 'Клієнт Гамма', 'Клієнт Альфа'],
            'status' => ['Клієнт Гамма', 'Клієнт Бета', 'Клієнт Альфа'],
        ];

        foreach ($expectedOrders as $column => $clientNames) {
            $this->actingAs($user)
                ->get(route('orders.clients.index', [
                    'sort' => $column,
                    'direction' => 'asc',
                ]))
                ->assertOk()
                ->assertSeeInOrder($clientNames);
        }

        $this->actingAs($user)
            ->get(route('orders.clients.index', [
                'sort' => 'name',
                'direction' => 'desc',
            ]))
            ->assertOk()
            ->assertSeeInOrder(['Клієнт Гамма', 'Клієнт Бета', 'Клієнт Альфа']);

        $this->actingAs($user)
            ->get(route('orders.clients.show', ['client' => $alphaClient, 'section' => 'orders']))
            ->assertOk()
            ->assertViewHas('readOnly', true)
            ->assertViewHas('clientOrders', function ($orders) use ($alphaClient): bool {
                return $orders->count() === 4
                    && $orders->every(fn (Order $order): bool => $order->client_id === $alphaClient->id);
            })
            ->assertSee('Картка клієнта. Клієнт Альфа')
            ->assertSee('client-orders-table', false)
            ->assertSeeInOrder(['Дата', 'Статус', 'Номер замовлення'])
            ->assertSee('order_sort=status', false)
            ->assertSee('Нове')
            ->assertSee('Номер замовлення')
            ->assertSee('Оплата')
            ->assertSee('До сплати')
            ->assertSee('Вартість')
            ->assertSee($alphaOrders[0]->order_number)
            ->assertDontSee($otherClientOrder->order_number)
            ->assertDontSee('Повернутись до замовників')
            ->assertSeeInOrder(['Редагувати', 'Деактивувати'])
            ->assertSee(route('orders.clients.edit', $alphaClient), false)
            ->assertSee(route('orders.clients.deactivate', $alphaClient), false)
            ->assertSee('<fieldset disabled>', false)
            ->assertSee('@click="openCreatePayment()"', false)
            ->assertSee('Внести платіж')
            ->assertSee('showPaymentModal', false);

        $this->actingAs($user)
            ->get(route('orders.clients.show', [
                'client' => $alphaClient,
                'section' => 'orders',
                'order_sort' => 'payment',
                'order_direction' => 'asc',
            ]))
            ->assertOk()
            ->assertViewHas('clientOrders', fn ($orders): bool => $orders->pluck('id')->all() === $alphaOrders->pluck('id')->all());

        $this->actingAs($user)
            ->get(route('orders.clients.show', [
                'client' => $alphaClient,
                'section' => 'orders',
                'order_sort' => 'status',
                'order_direction' => 'asc',
            ]))
            ->assertOk()
            ->assertViewHas('orderSort', 'status');

        $this->actingAs($user)
            ->get('/orders/clients/'.$alphaClient->id)
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('orders.clients.edit', $alphaClient))
            ->assertOk()
            ->assertViewHas('readOnly', false)
            ->assertSee('@click="openCreatePayment()"', false);
    }
}
