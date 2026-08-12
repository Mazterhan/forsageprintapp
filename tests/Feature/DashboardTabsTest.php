<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Order;
use App\Models\OrderProposal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesRoles;
use Tests\TestCase;

class DashboardTabsTest extends TestCase
{
    use CreatesRoles;
    use RefreshDatabase;

    public function test_dashboard_defaults_to_proposals_tab_and_keeps_its_filters_scoped(): void
    {
        $user = $this->analyticsUser();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Аналітика')
            ->assertSee('Заявки')
            ->assertSee('Замовлення')
            ->assertSee('value="proposals"', false)
            ->assertSee('aria-selected="true"', false)
            ->assertSee('<input type="hidden" name="tab" value="proposals">', false)
            ->assertSee(route('dashboard', ['tab' => 'proposals']), false)
            ->assertSee('Кількість заявок');
    }

    public function test_orders_tab_calculates_live_payment_statuses_without_rendering_proposals(): void
    {
        $user = $this->analyticsUser();
        $client = Client::factory()->create(['name' => 'Клієнт аналітики']);
        $otherClient = Client::factory()->create(['name' => 'Інший клієнт']);
        $orders = collect(range(0, 3))->map(fn (int $index): Order => Order::factory()->create([
            'client_id' => $index === 0 ? $otherClient->id : $client->id,
            'customer_name' => $index === 0 ? $otherClient->name : $client->name,
            'last_edited_by' => $user->id,
            'total_cost' => 1000,
            'amount_due' => 1000,
        ]));

        foreach ([1 => 200, 2 => 1000, 3 => 1200] as $orderIndex => $amount) {
            ClientPayment::query()->create([
                'client_id' => $client->id,
                'order_id' => $orders[$orderIndex]->id,
                'amount' => $amount,
                'amount_uah' => $amount,
                'currency' => 'UAH',
                'payment_type' => 'order',
                'paid_at' => now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }
        ClientPayment::query()->create([
            'client_id' => $client->id,
            'amount' => 1500,
            'amount_uah' => 1500,
            'currency' => 'UAH',
            'payment_type' => 'prepayment',
            'paid_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        ClientPayment::query()->create([
            'client_id' => $client->id,
            'amount' => 300,
            'amount_uah' => 300,
            'currency' => 'UAH',
            'payment_type' => 'writeoff',
            'is_from_overpayment' => true,
            'paid_at' => now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $proposal = OrderProposal::factory()->forUser($user)->create([
            'proposal_number' => 'P-HIDDEN-IN-ORDERS',
        ]);

        $response = $this->actingAs($user)
            ->get(route('dashboard', ['tab' => 'orders', 'period' => 'all']));

        $response
            ->assertOk()
            ->assertSee('<input type="hidden" name="tab" value="orders">', false)
            ->assertSee('Кількість замовлень')
            ->assertSee('Вартість замовлень (грн)')
            ->assertSee('4 000.00')
            ->assertSee('2 400.00')
            ->assertSee('1 600.00')
            ->assertSee('Не сплачено')
            ->assertSee('Частково сплачено')
            ->assertSee('Сплачено')
            ->assertSee('Є переплата')
            ->assertSee('Клієнти-боржники')
            ->assertSee('Клієнти-інвестори')
            ->assertSee('Заборгованість клієнтів (грн)')
            ->assertSee('Поточна сума переплат (грн)')
            ->assertSee('Боржники за замовленнями')
            ->assertSee('Інвестори — поточні переплати')
            ->assertSee('Зведення за статусами оплати')
            ->assertSee('Зведення за статусами замовлень')
            ->assertSee('ordersAnalyticsModal')
            ->assertSee('data-period-sort="updated_at"', false)
            ->assertSee('data-period-sort="number"', false)
            ->assertSee('data-period-sort="payment_status"', false)
            ->assertSee('data-period-sort="customer"', false)
            ->assertSee('data-period-sort="user"', false)
            ->assertSee('data-period-sort="total_cost"', false)
            ->assertSee('data-period-sort="payments_total"', false)
            ->assertSee('data-period-sort="amount_due"', false)
            ->assertSee('href="'.route('orders.clients.show', $client).'"', false)
            ->assertSee('Замовлення у вибраному періоді')
            ->assertDontSee($proposal->proposal_number);
        $response
            ->assertViewHas('debtorClients', fn ($debtors): bool => $debtors->count() === 2
                && (float) $debtors->sum('debt_total') === 1800.0)
            ->assertViewHas('investorClients', fn ($investors): bool => $investors->count() === 1
                && (float) $investors->first()['overpayment_total'] === 1200.0)
            ->assertViewHas('kpi', fn (array $kpi): bool => $kpi['debtor_clients'] === 2
                && (float) $kpi['debt_total'] === 1800.0
                && $kpi['investor_clients'] === 1
                && (float) $kpi['investor_total'] === 1200.0);

        foreach ($orders as $order) {
            $response->assertSee($order->order_number);
        }
    }

    public function test_orders_tables_show_top_ten_and_only_actual_clickable_statuses(): void
    {
        $user = $this->analyticsUser();

        foreach (range(1, 12) as $index) {
            $client = Client::factory()->create(['name' => 'Боржник '.$index]);
            Order::factory()->create([
                'client_id' => $client->id,
                'customer_name' => $client->name,
                'last_edited_by' => $user->id,
                'status' => Order::STATUS_NEW,
                'total_cost' => 1000 + $index,
                'amount_due' => 1000 + $index,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('dashboard', ['tab' => 'orders', 'period' => 'all']))
            ->assertOk()
            ->assertSee('Боржники за замовленнями (ТОП 10)')
            ->assertSee('Інвестори — поточні переплати (ТОП 10)');

        $html = $response->getContent();
        $this->assertSame(10, substr_count($html, 'dashboard-top-debtor-row'));
        $this->assertSame(1, substr_count($html, 'data-analytics-popup="payment-status"'));
        $this->assertSame(1, substr_count($html, 'data-analytics-popup="order-status"'));
        $response->assertViewHas('debtorClients', fn ($rows): bool => $rows->count() === 12);
    }

    public function test_orders_tab_client_filter_does_not_reuse_proposal_tab_state(): void
    {
        $user = $this->analyticsUser();
        $firstClient = Client::factory()->create(['name' => 'Перший клієнт']);
        $secondClient = Client::factory()->create(['name' => 'Другий клієнт']);
        $firstOrder = Order::factory()->create([
            'client_id' => $firstClient->id,
            'customer_name' => $firstClient->name,
            'last_edited_by' => $user->id,
        ]);
        $secondOrder = Order::factory()->create([
            'client_id' => $secondClient->id,
            'customer_name' => $secondClient->name,
            'last_edited_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', [
                'tab' => 'orders',
                'period' => 'all',
                'client_id' => [$firstClient->id],
            ]))
            ->assertOk()
            ->assertSee($firstOrder->order_number)
            ->assertDontSee($secondOrder->order_number)
            ->assertSee(route('dashboard', ['tab' => 'orders']), false);

        $this->actingAs($user)
            ->get(route('dashboard', ['tab' => 'unsupported']))
            ->assertOk()
            ->assertSee('Кількість заявок')
            ->assertDontSee('Кількість замовлень');
    }

    public function test_orders_analytics_tab_and_its_content_require_a_separate_permission(): void
    {
        $user = $this->createUserWithRole([
            'can_analytics' => true,
            'analytics_show_kpi' => true,
            'analytics_show_charts' => true,
            'analytics_show_tables' => true,
            'analytics_finance_access' => true,
            'analytics_orders_access' => false,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Заявки')
            ->assertDontSee('value="orders"', false);

        $this->actingAs($user)
            ->get(route('dashboard', ['tab' => 'orders']))
            ->assertForbidden();
    }

    private function analyticsUser()
    {
        return $this->createUserWithRole([
            'can_analytics' => true,
            'analytics_show_kpi' => true,
            'analytics_show_charts' => true,
            'analytics_show_tables' => true,
            'analytics_finance_access' => true,
            'can_orders' => true,
            'orders_proposals' => true,
            'orders_list_scope' => 'all',
            'orders_clients_manage' => true,
        ]);
    }
}
