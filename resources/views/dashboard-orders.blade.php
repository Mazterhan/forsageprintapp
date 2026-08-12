<x-app-layout>
    @section('title', __('Аналітика — Замовлення'))

    <x-slot name="header">
        @include('dashboard.partials.header-tabs', ['activeTab' => $activeTab])
    </x-slot>

    @php
        $period = $filters['period'] ?? 'mtd';
        $selectedClientIds = collect($filters['client_id'] ?? [])->map(static fn ($value) => (int) $value)->all();
        $selectedClientNames = $clients
            ->filter(fn ($client) => in_array((int) $client->id, $selectedClientIds, true))
            ->pluck('name')
            ->values()
            ->all();
        $showKpi = (bool) ($dashboardPermissions['show_kpi'] ?? false);
        $showTables = (bool) ($dashboardPermissions['show_tables'] ?? false);
        $showFinance = (bool) ($dashboardPermissions['show_finance'] ?? false);
        $canOpenOrder = (bool) ($dashboardPermissions['can_open_order'] ?? false);
        $canOpenClient = (bool) ($dashboardPermissions['can_open_client'] ?? false);
        $formatMoney = static fn ($value): string => number_format((float) $value, 2, '.', ' ');
        $formatDate = static fn ($value): string => $value
            ? $value->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i')
            : '—';
        $orderCount = max(1, (int) ($kpi['order_count'] ?? 0));

        $kpiCards = [
            ['label' => 'Кількість замовлень', 'value' => number_format((int) ($kpi['order_count'] ?? 0), 0, '.', ' ')],
            ['label' => 'Унікальні замовники', 'value' => number_format((int) ($kpi['unique_clients'] ?? 0), 0, '.', ' ')],
            ['label' => 'Клієнти-боржники', 'value' => number_format((int) ($kpi['debtor_clients'] ?? 0), 0, '.', ' ')],
            ['label' => 'Клієнти-інвестори', 'value' => number_format((int) ($kpi['investor_clients'] ?? 0), 0, '.', ' ')],
        ];
        if ($showFinance) {
            $kpiCards = array_merge($kpiCards, [
                ['label' => 'Вартість замовлень (грн)', 'value' => $formatMoney($kpi['total_cost'] ?? 0)],
                ['label' => 'Загальна сума сплат (грн)', 'value' => $formatMoney($kpi['payments_total'] ?? 0)],
                ['label' => 'Сума до сплати (грн)', 'value' => $formatMoney($kpi['amount_due'] ?? 0)],
                ['label' => 'Середній чек (грн)', 'value' => $formatMoney($kpi['average_check'] ?? 0)],
                ['label' => 'Заборгованість клієнтів (грн)', 'value' => $formatMoney($kpi['debt_total'] ?? 0)],
                ['label' => 'Поточна сума переплат (грн)', 'value' => $formatMoney($kpi['investor_total'] ?? 0)],
            ]);
        }

        $modalDebtors = $debtorClients->map(function (array $row) use ($showFinance, $canOpenClient): array {
            $result = [
                'client' => $row['client_name'],
                'orders_count' => (int) $row['orders_count'],
                'client_url' => $canOpenClient && $row['client_public_id'] ? route('orders.clients.show', $row['client_public_id']) : null,
            ];
            if ($showFinance) {
                $result += [
                    'total_cost' => (float) $row['total_cost'],
                    'payments_total' => (float) $row['payments_total'],
                    'debt_total' => (float) $row['debt_total'],
                ];
            }

            return $result;
        })->values();
        $modalInvestors = $investorClients->map(function (array $row) use ($showFinance, $canOpenClient): array {
            $result = [
                'client' => $row['client_name'],
                'client_url' => $canOpenClient && $row['client_public_id'] ? route('orders.clients.show', $row['client_public_id']) : null,
            ];
            if ($showFinance) {
                $result['overpayment_total'] = (float) $row['overpayment_total'];
            }

            return $result;
        })->values();
        $modalOrders = $analyticsOrders->map(function (array $row) use ($showFinance, $canOpenOrder, $canOpenClient): array {
            $result = [
                'updated_at' => $row['updated_at']?->timestamp ?? 0,
                'updated_at_label' => $row['updated_at']?->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i') ?? '—',
                'number' => $row['number'],
                'order_url' => $canOpenOrder ? route('orders.show', $row['public_id']) : null,
                'payment_status' => $row['status'],
                'payment_status_label' => $row['status_label'],
                'payment_status_class' => $row['status_class'],
                'order_status' => $row['order_status'],
                'order_status_label' => $row['order_status_label'],
                'order_status_class' => $row['order_status_class'],
                'customer' => $row['customer'],
                'client_url' => $canOpenClient && $row['client_public_id'] ? route('orders.clients.show', $row['client_public_id']) : null,
                'user' => $row['user'],
            ];
            if ($showFinance) {
                $result += [
                    'total_cost' => (float) $row['total_cost'],
                    'payments_total' => (float) $row['payments_total'],
                    'amount_due' => (float) $row['amount_due'],
                ];
            }

            return $result;
        })->values();
    @endphp

    <style>
        .orders-analytics-shell {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            align-items: stretch;
        }

        .orders-analytics-filters {
            width: 100%;
            flex: 0 0 auto;
        }

        .orders-analytics-content {
            width: 100%;
            min-width: 0;
        }

        .orders-analytics-panel {
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .orders-analytics-panel:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
        }

        .orders-analytics-popup-trigger {
            cursor: pointer;
        }

        .orders-analytics-popup-trigger:focus-visible {
            outline: 3px solid rgba(79, 70, 229, 0.35);
            outline-offset: 2px;
        }

        .orders-analytics-sort-button {
            display: inline-flex;
            width: 100%;
            align-items: center;
            gap: 0.35rem;
            font-weight: 600;
        }

        .orders-analytics-sort-button.is-right {
            justify-content: flex-end;
        }

        .orders-analytics-kpi-grid,
        .orders-analytics-status-grid,
        .orders-analytics-client-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1rem;
        }

        .orders-analytics-client-dropdown {
            position: relative;
        }

        .orders-analytics-client-dropdown-panel {
            position: absolute;
            top: calc(100% + 0.35rem);
            left: 0;
            right: 0;
            z-index: 80;
            display: none;
            max-height: 260px;
            overflow-y: auto;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.75rem;
            background: #fff;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
        }

        .orders-analytics-client-dropdown.is-open .orders-analytics-client-dropdown-panel {
            display: block;
        }

        @media (min-width: 900px) {
            .orders-analytics-status-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .orders-analytics-client-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1100px) {
            .orders-analytics-kpi-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .orders-analytics-status-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .orders-analytics-shell {
                flex-direction: row;
                align-items: flex-start;
            }

            .orders-analytics-filters {
                position: sticky;
                top: 1.5rem;
                width: 242px;
            }

            .orders-analytics-content {
                max-width: 1550px;
            }
        }

        @media (min-width: 1450px) {
            .orders-analytics-kpi-grid {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }
        }

    </style>

    <div class="py-8">
        <div class="max-w-[1800px] mx-auto px-6 sm:px-8 lg:px-12">
            <div class="orders-analytics-shell">
                <aside class="orders-analytics-filters orders-analytics-panel h-fit rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <form method="GET" action="{{ route('dashboard') }}" class="space-y-4">
                        <input type="hidden" name="tab" value="orders">

                        <div>
                            <label for="ordersAnalyticsPeriod" class="mb-1 block text-sm font-medium text-gray-700">Період</label>
                            <select id="ordersAnalyticsPeriod" name="period" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="all" @selected($period === 'all')>За весь період</option>
                                <option value="ytd" @selected($period === 'ytd')>З початку поточного року</option>
                                <option value="mtd" @selected($period === 'mtd')>З початку поточного місяця</option>
                                <option value="wtd" @selected($period === 'wtd')>З початку поточного тижня</option>
                                <option value="custom" @selected($period === 'custom')>Кастомний період</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label for="ordersAnalyticsFrom" class="mb-1 block text-sm font-medium text-gray-700">Від</label>
                                <input id="ordersAnalyticsFrom" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            </div>
                            <div>
                                <label for="ordersAnalyticsTo" class="mb-1 block text-sm font-medium text-gray-700">До</label>
                                <input id="ordersAnalyticsTo" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Замовник</label>
                            <div id="ordersAnalyticsClientDropdown" class="orders-analytics-client-dropdown">
                                <button id="ordersAnalyticsClientToggle" type="button" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm text-gray-700 shadow-sm">
                                    <span id="ordersAnalyticsClientLabel">
                                        @if(count($selectedClientNames) === 0)
                                            Усі замовники
                                        @elseif(count($selectedClientNames) === 1)
                                            {{ $selectedClientNames[0] }}
                                        @else
                                            Обрано: {{ count($selectedClientNames) }}
                                        @endif
                                    </span>
                                </button>
                                <div class="orders-analytics-client-dropdown-panel">
                                    @forelse($clients as $client)
                                        <label class="flex items-center gap-2 px-1 py-1.5 text-sm text-gray-900">
                                            <input type="checkbox" name="client_id[]" value="{{ $client->id }}" @checked(in_array((int) $client->id, $selectedClientIds, true))>
                                            <span>{{ $client->name }}</span>
                                        </label>
                                    @empty
                                        <div class="px-1 py-2 text-sm text-gray-500">Немає клієнтів для аналітики</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        @if(!empty($periodError))
                            <div class="text-xs text-red-600">{{ $periodError }}</div>
                        @endif

                        <div class="flex items-center gap-2 pt-2">
                            <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Застосувати</button>
                            <a href="{{ route('dashboard', ['tab' => 'orders']) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Скинути</a>
                        </div>
                    </form>
                </aside>

                <section class="orders-analytics-content space-y-6">
                    @if($showKpi)
                        <div class="orders-analytics-kpi-grid">
                            @foreach($kpiCards as $card)
                                <div class="orders-analytics-panel rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                                    <div class="text-xs uppercase tracking-wide text-gray-500">{{ $card['label'] }}</div>
                                    <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $card['value'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div>
                            <h3 class="mb-3 text-sm font-semibold uppercase text-gray-700">Статуси оплати замовлень</h3>
                            <div class="orders-analytics-status-grid">
                                @foreach($statusStats as $status)
                                    @php($share = ((int) ($kpi['order_count'] ?? 0)) > 0 ? ((int) $status['count'] / (int) $kpi['order_count']) * 100 : 0)
                                    <div class="orders-analytics-panel rounded-lg border bg-white p-4 shadow-sm {{ $status['className'] }}">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-sm font-semibold">{{ $status['label'] }}</span>
                                            <span class="text-xl font-bold">{{ number_format((int) $status['count'], 0, '.', ' ') }}</span>
                                        </div>
                                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/70">
                                            <div class="h-full rounded-full bg-current opacity-60" style="width: {{ number_format($share, 2, '.', '') }}%"></div>
                                        </div>
                                        <div class="mt-1 text-right text-xs font-semibold">{{ number_format($share, 1, '.', ' ') }}%</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($showTables)
                        <div class="orders-analytics-client-grid">
                            <div class="orders-analytics-panel orders-analytics-popup-trigger overflow-hidden rounded-lg border border-red-200 bg-white shadow-sm" data-analytics-popup="debtors" role="button" tabindex="0" aria-label="Відкрити повну таблицю боржників">
                                <div class="border-b border-red-200 bg-rose-50 px-4 py-3">
                                    <div class="font-semibold text-red-900">Боржники за замовленнями (ТОП 10)</div>
                                    <div class="mt-1 text-xs text-red-700">Позитивний залишок до сплати за замовленнями у вибраному періоді.</div>
                                </div>
                                <div class="max-h-[420px] overflow-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="sticky top-0 bg-rose-100">
                                            <tr>
                                                <th class="border-b px-3 py-2 text-left">Клієнт</th>
                                                <th class="border-b px-3 py-2 text-right">Замовлень</th>
                                                @if($showFinance)
                                                    <th class="border-b px-3 py-2 text-right">Вартість</th>
                                                    <th class="border-b px-3 py-2 text-right">Сплачено</th>
                                                    <th class="border-b px-3 py-2 text-right">Борг</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($debtorClients->take(10) as $debtor)
                                                <tr class="dashboard-top-debtor-row border-t border-gray-200 {{ $loop->odd ? 'bg-white' : 'bg-gray-50' }}">
                                                    <td class="px-3 py-2 font-medium">
                                                        @if($canOpenClient && $debtor['client_public_id'])
                                                            <a href="{{ route('orders.clients.show', $debtor['client_public_id']) }}" class="text-indigo-600 hover:text-indigo-900">{{ $debtor['client_name'] }}</a>
                                                        @else
                                                            {{ $debtor['client_name'] }}
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-right">{{ number_format((int) $debtor['orders_count'], 0, '.', ' ') }}</td>
                                                    @if($showFinance)
                                                        <td class="px-3 py-2 text-right">{{ $formatMoney($debtor['total_cost']) }}</td>
                                                        <td class="px-3 py-2 text-right">{{ $formatMoney($debtor['payments_total']) }}</td>
                                                        <td class="px-3 py-2 text-right font-bold text-red-700">{{ $formatMoney($debtor['debt_total']) }}</td>
                                                    @endif
                                                </tr>
                                            @empty
                                                <tr><td colspan="{{ $showFinance ? 5 : 2 }}" class="px-3 py-8 text-center text-gray-500">Клієнтів із заборгованістю не знайдено.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="orders-analytics-panel orders-analytics-popup-trigger overflow-hidden rounded-lg border border-blue-200 bg-white shadow-sm" data-analytics-popup="investors" role="button" tabindex="0" aria-label="Відкрити повну таблицю інвесторів">
                                <div class="border-b border-blue-200 bg-blue-50 px-4 py-3">
                                    <div class="font-semibold text-blue-900">Інвестори — поточні переплати (ТОП 10)</div>
                                    <div class="mt-1 text-xs text-blue-700">Поточний доступний баланс переплати. Період не змінює баланс; фільтр клієнтів застосовується.</div>
                                </div>
                                <div class="max-h-[420px] overflow-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="sticky top-0 bg-blue-100">
                                            <tr>
                                                <th class="border-b px-3 py-2 text-left">Клієнт</th>
                                                @if($showFinance)
                                                    <th class="border-b px-3 py-2 text-right">Поточна переплата</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($investorClients->take(10) as $investor)
                                                <tr class="dashboard-top-investor-row border-t border-gray-200 {{ $loop->odd ? 'bg-white' : 'bg-gray-50' }}">
                                                    <td class="px-3 py-2 font-medium">
                                                        @if($canOpenClient && $investor['client_public_id'])
                                                            <a href="{{ route('orders.clients.show', $investor['client_public_id']) }}" class="text-indigo-600 hover:text-indigo-900">{{ $investor['client_name'] }}</a>
                                                        @else
                                                            {{ $investor['client_name'] }}
                                                        @endif
                                                    </td>
                                                    @if($showFinance)
                                                        <td class="px-3 py-2 text-right font-bold text-blue-700">{{ $formatMoney($investor['overpayment_total']) }}</td>
                                                    @endif
                                                </tr>
                                            @empty
                                                <tr><td colspan="{{ $showFinance ? 2 : 1 }}" class="px-3 py-8 text-center text-gray-500">Клієнтів із поточною переплатою не знайдено.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="orders-analytics-client-grid">
                            <div class="orders-analytics-panel overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                                <div class="border-b bg-gray-50 px-4 py-3 font-semibold text-gray-800">Зведення за статусами оплати</div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                    <thead style="background-color: #FCEEDF;">
                                        <tr>
                                            <th class="border-b px-3 py-2 text-left">Статус</th>
                                            <th class="border-b px-3 py-2 text-right">Кількість</th>
                                            @if($showFinance)
                                                <th class="border-b px-3 py-2 text-right">Вартість</th>
                                                <th class="border-b px-3 py-2 text-right">Сплачено</th>
                                                <th class="border-b px-3 py-2 text-right">До сплати</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(collect($statusStats)->where('count', '>', 0) as $status)
                                            <tr class="orders-analytics-popup-trigger border-t border-gray-200 hover:bg-indigo-50" data-analytics-popup="payment-status" data-status="{{ $status['key'] }}" role="button" tabindex="0">
                                                <td class="px-3 py-2"><span class="inline-flex rounded-md border px-2 py-1 text-xs font-semibold {{ $status['className'] }}">{{ $status['label'] }}</span></td>
                                                <td class="px-3 py-2 text-right font-semibold">{{ number_format((int) $status['count'], 0, '.', ' ') }}</td>
                                                @if($showFinance)
                                                    <td class="px-3 py-2 text-right">{{ $formatMoney($status['total_cost']) }}</td>
                                                    <td class="px-3 py-2 text-right">{{ $formatMoney($status['payments_total']) }}</td>
                                                    <td class="px-3 py-2 text-right">{{ $formatMoney($status['amount_due']) }}</td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="orders-analytics-panel overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                                <div class="border-b bg-gray-50 px-4 py-3 font-semibold text-gray-800">Зведення за статусами замовлень</div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead style="background-color: #FCEEDF;">
                                            <tr>
                                                <th class="border-b px-3 py-2 text-left">Статус</th>
                                                <th class="border-b px-3 py-2 text-right">Кількість</th>
                                                @if($showFinance)
                                                    <th class="border-b px-3 py-2 text-right">Вартість</th>
                                                    <th class="border-b px-3 py-2 text-right">Сплачено</th>
                                                    <th class="border-b px-3 py-2 text-right">До сплати</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($orderStatusStats as $status)
                                                <tr class="orders-analytics-popup-trigger border-t border-gray-200 hover:bg-indigo-50" data-analytics-popup="order-status" data-status="{{ $status['key'] }}" role="button" tabindex="0">
                                                    <td class="px-3 py-2"><span class="inline-flex rounded-md border px-2 py-1 text-xs font-semibold {{ $status['className'] }}">{{ $status['label'] }}</span></td>
                                                    <td class="px-3 py-2 text-right font-semibold">{{ number_format((int) $status['count'], 0, '.', ' ') }}</td>
                                                    @if($showFinance)
                                                        <td class="px-3 py-2 text-right">{{ $formatMoney($status['total_cost']) }}</td>
                                                        <td class="px-3 py-2 text-right">{{ $formatMoney($status['payments_total']) }}</td>
                                                        <td class="px-3 py-2 text-right">{{ $formatMoney($status['amount_due']) }}</td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="orders-analytics-panel overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b bg-gray-50 px-4 py-3 font-semibold text-gray-800">Замовлення у вибраному періоді (до 100 останніх)</div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead style="background-color: #D3D4D4;">
                                        <tr>
                                            <th class="border-b px-3 py-2 text-left"><button type="button" class="orders-analytics-sort-button" data-period-sort="updated_at" data-sort-type="number">Дата <span>↕</span></button></th>
                                            <th class="border-b px-3 py-2 text-left"><button type="button" class="orders-analytics-sort-button" data-period-sort="number" data-sort-type="text">Номер замовлення <span>↕</span></button></th>
                                            <th class="border-b px-3 py-2 text-left"><button type="button" class="orders-analytics-sort-button" data-period-sort="payment_status" data-sort-type="text">Оплата <span>↕</span></button></th>
                                            <th class="border-b px-3 py-2 text-left"><button type="button" class="orders-analytics-sort-button" data-period-sort="customer" data-sort-type="text">Замовник <span>↕</span></button></th>
                                            <th class="border-b px-3 py-2 text-left"><button type="button" class="orders-analytics-sort-button" data-period-sort="user" data-sort-type="text">Користувач <span>↕</span></button></th>
                                            @if($showFinance)
                                                <th class="border-b px-3 py-2 text-right"><button type="button" class="orders-analytics-sort-button is-right" data-period-sort="total_cost" data-sort-type="number">Вартість <span>↕</span></button></th>
                                                <th class="border-b px-3 py-2 text-right"><button type="button" class="orders-analytics-sort-button is-right" data-period-sort="payments_total" data-sort-type="number">Сплачено <span>↕</span></button></th>
                                                <th class="border-b px-3 py-2 text-right"><button type="button" class="orders-analytics-sort-button is-right" data-period-sort="amount_due" data-sort-type="number">До сплати <span>↕</span></button></th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody id="ordersAnalyticsPeriodTableBody">
                                        @forelse($analyticsOrders as $order)
                                            <tr class="orders-analytics-period-row border-t border-gray-200 {{ $loop->odd ? 'bg-gray-50' : 'bg-white' }}">
                                                <td class="px-3 py-2" data-sort-field="updated_at" data-sort-value="{{ $order['updated_at']?->timestamp ?? 0 }}">{{ $formatDate($order['updated_at']) }}</td>
                                                <td class="px-3 py-2 font-medium" data-sort-field="number" data-sort-value="{{ $order['number'] }}">
                                                    @if($canOpenOrder)
                                                        <a href="{{ route('orders.show', $order['public_id']) }}" class="text-indigo-600 hover:text-indigo-900">{{ $order['number'] }}</a>
                                                    @else
                                                        {{ $order['number'] }}
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2" data-sort-field="payment_status" data-sort-value="{{ $order['status_label'] }}"><span class="inline-flex rounded-md border px-2 py-1 text-xs font-semibold {{ $order['status_class'] }}">{{ $order['status_label'] }}</span></td>
                                                <td class="px-3 py-2" data-sort-field="customer" data-sort-value="{{ $order['customer'] }}">
                                                    @if($canOpenClient && $order['client_public_id'])
                                                        <a href="{{ route('orders.clients.show', $order['client_public_id']) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">{{ $order['customer'] }}</a>
                                                    @else
                                                        {{ $order['customer'] }}
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2" data-sort-field="user" data-sort-value="{{ $order['user'] }}">{{ $order['user'] }}</td>
                                                @if($showFinance)
                                                    <td class="px-3 py-2 text-right" data-sort-field="total_cost" data-sort-value="{{ $order['total_cost'] }}">{{ $formatMoney($order['total_cost']) }}</td>
                                                    <td class="px-3 py-2 text-right" data-sort-field="payments_total" data-sort-value="{{ $order['payments_total'] }}">{{ $formatMoney($order['payments_total']) }}</td>
                                                    <td class="px-3 py-2 text-right font-semibold" data-sort-field="amount_due" data-sort-value="{{ $order['amount_due'] }}">{{ $formatMoney($order['amount_due']) }}</td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr><td colspan="{{ $showFinance ? 8 : 5 }}" class="px-3 py-8 text-center text-gray-500">За обраними фільтрами замовлень не знайдено.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if(!$showKpi && !$showTables)
                        <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500 shadow-sm">Для вашої ролі перегляд аналітичних блоків не дозволений.</div>
                    @endif
                </section>
            </div>
        </div>
    </div>

    @if($showTables)
        <div id="ordersAnalyticsModal" class="fixed inset-0 z-[200] hidden items-center justify-center bg-gray-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="ordersAnalyticsModalTitle">
            <div class="flex max-h-[92vh] w-full max-w-[1500px] flex-col overflow-hidden rounded-xl border border-gray-400 bg-gray-100 shadow-2xl">
                <div class="flex items-center justify-between gap-4 border-b border-gray-300 bg-gray-200 px-5 py-4">
                    <h2 id="ordersAnalyticsModalTitle" class="text-lg font-semibold text-gray-900"></h2>
                    <button id="ordersAnalyticsModalClose" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-gray-300 bg-white text-xl text-gray-600 hover:bg-gray-100" aria-label="Закрити">×</button>
                </div>
                <div class="overflow-auto bg-gray-100 p-2">
                    <table class="min-w-full text-sm">
                        <thead id="ordersAnalyticsModalHead" class="sticky top-0 z-10 bg-[#FCEEDF]"></thead>
                        <tbody id="ordersAnalyticsModalBody"></tbody>
                    </table>
                </div>
                <div id="ordersAnalyticsModalCount" class="border-t border-gray-300 bg-gray-200 px-5 py-3 text-sm text-gray-700"></div>
            </div>
        </div>
    @endif

    <script>
        (function () {
            const periodSelect = document.getElementById('ordersAnalyticsPeriod');
            const fromInput = document.getElementById('ordersAnalyticsFrom');
            const toInput = document.getElementById('ordersAnalyticsTo');
            const clientDropdown = document.getElementById('ordersAnalyticsClientDropdown');
            const clientToggle = document.getElementById('ordersAnalyticsClientToggle');
            const clientLabel = document.getElementById('ordersAnalyticsClientLabel');
            const clientCheckboxes = clientDropdown
                ? clientDropdown.querySelectorAll('input[type="checkbox"][name="client_id[]"]')
                : [];

            function syncCustomPeriod() {
                if (periodSelect && ((fromInput && fromInput.value) || (toInput && toInput.value))) {
                    periodSelect.value = 'custom';
                }
            }

            function syncClientLabel() {
                if (!clientLabel) return;
                const checked = Array.from(clientCheckboxes).filter((checkbox) => checkbox.checked);
                if (checked.length === 0) {
                    clientLabel.textContent = 'Усі замовники';
                } else if (checked.length === 1) {
                    clientLabel.textContent = checked[0].closest('label')?.querySelector('span')?.textContent?.trim() || 'Обрано: 1';
                } else {
                    clientLabel.textContent = `Обрано: ${checked.length}`;
                }
            }

            fromInput?.addEventListener('change', syncCustomPeriod);
            fromInput?.addEventListener('input', syncCustomPeriod);
            toInput?.addEventListener('change', syncCustomPeriod);
            toInput?.addEventListener('input', syncCustomPeriod);
            clientToggle?.addEventListener('click', () => clientDropdown?.classList.toggle('is-open'));
            clientCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', syncClientLabel));
            document.addEventListener('click', (event) => {
                if (clientDropdown && !clientDropdown.contains(event.target)) {
                    clientDropdown.classList.remove('is-open');
                }
            });

            const periodTableBody = document.getElementById('ordersAnalyticsPeriodTableBody');
            const periodSortButtons = document.querySelectorAll('[data-period-sort]');
            let periodSortKey = '';
            let periodSortDirection = 'asc';

            periodSortButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const key = button.dataset.periodSort;
                    const type = button.dataset.sortType || 'text';
                    if (periodSortKey === key) {
                        periodSortDirection = periodSortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        periodSortKey = key;
                        periodSortDirection = 'asc';
                    }

                    const rows = Array.from(periodTableBody?.querySelectorAll('.orders-analytics-period-row') || []);
                    rows.sort((left, right) => {
                        const leftValue = left.querySelector(`[data-sort-field="${key}"]`)?.dataset.sortValue ?? '';
                        const rightValue = right.querySelector(`[data-sort-field="${key}"]`)?.dataset.sortValue ?? '';
                        const comparison = type === 'number'
                            ? Number(leftValue || 0) - Number(rightValue || 0)
                            : String(leftValue).localeCompare(String(rightValue), 'uk', { numeric: true, sensitivity: 'base' });

                        return comparison * (periodSortDirection === 'desc' ? -1 : 1);
                    });
                    rows.forEach((row, index) => {
                        row.classList.remove('bg-gray-50', 'bg-white');
                        row.classList.add(index % 2 === 0 ? 'bg-gray-50' : 'bg-white');
                        periodTableBody.appendChild(row);
                    });
                    periodSortButtons.forEach((sortButton) => {
                        const indicator = sortButton.querySelector('span');
                        if (indicator) {
                            indicator.textContent = sortButton.dataset.periodSort === periodSortKey
                                ? (periodSortDirection === 'asc' ? '▲' : '▼')
                                : '↕';
                        }
                    });
                });
            });

            const analyticsData = {
                debtors: @json($modalDebtors),
                investors: @json($modalInvestors),
                orders: @json($modalOrders),
            };
            const showFinance = @json($showFinance);
            const modal = document.getElementById('ordersAnalyticsModal');
            const modalTitle = document.getElementById('ordersAnalyticsModalTitle');
            const modalHead = document.getElementById('ordersAnalyticsModalHead');
            const modalBody = document.getElementById('ordersAnalyticsModalBody');
            const modalCount = document.getElementById('ordersAnalyticsModalCount');
            const modalClose = document.getElementById('ordersAnalyticsModalClose');
            let modalRows = [];
            let modalColumns = [];
            let sortKey = '';
            let sortDirection = 'asc';

            const money = (value) => Number(value || 0).toLocaleString('uk-UA', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
            const integer = (value) => Number(value || 0).toLocaleString('uk-UA');
            const textColumn = (key, label, options = {}) => ({ key, label, type: 'text', ...options });
            const numberColumn = (key, label, options = {}) => ({ key, label, type: 'number', align: 'right', ...options });

            const debtorColumns = [
                textColumn('client', 'Клієнт', { linkKey: 'client_url' }),
                numberColumn('orders_count', 'Замовлень', { format: integer }),
                ...(showFinance ? [
                    numberColumn('total_cost', 'Вартість', { format: money }),
                    numberColumn('payments_total', 'Сплачено', { format: money }),
                    numberColumn('debt_total', 'Борг', { format: money, className: 'font-bold text-red-700' }),
                ] : []),
            ];
            const investorColumns = [
                textColumn('client', 'Клієнт', { linkKey: 'client_url' }),
                ...(showFinance ? [numberColumn('overpayment_total', 'Поточна переплата', { format: money, className: 'font-bold text-blue-700' })] : []),
            ];
            const orderColumns = [
                numberColumn('updated_at', 'Дата', { displayKey: 'updated_at_label', align: 'left' }),
                textColumn('order_status_label', 'Статус замовлення', { badgeClassKey: 'order_status_class' }),
                textColumn('number', 'Номер замовлення', { linkKey: 'order_url' }),
                textColumn('payment_status_label', 'Оплата', { badgeClassKey: 'payment_status_class' }),
                textColumn('customer', 'Ім’я замовника', { linkKey: 'client_url' }),
                textColumn('user', 'Користувач'),
                ...(showFinance ? [
                    numberColumn('amount_due', 'До сплати', { format: money }),
                    numberColumn('total_cost', 'Вартість', { format: money }),
                ] : []),
            ];

            function compareRows(left, right, column) {
                const a = left[column.key];
                const b = right[column.key];
                if (column.type === 'number') {
                    return Number(a || 0) - Number(b || 0);
                }

                return String(a ?? '').localeCompare(String(b ?? ''), 'uk', { numeric: true, sensitivity: 'base' });
            }

            function renderModalTable() {
                const column = modalColumns.find((item) => item.key === sortKey) || modalColumns[0];
                const directionFactor = sortDirection === 'desc' ? -1 : 1;
                const rows = [...modalRows].sort((a, b) => compareRows(a, b, column) * directionFactor);

                const headerRow = document.createElement('tr');
                modalColumns.forEach((item) => {
                    const th = document.createElement('th');
                    th.className = `border-b px-3 py-3 ${item.align === 'right' ? 'text-right' : 'text-left'}`;
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = `orders-analytics-sort-button ${item.align === 'right' ? 'is-right' : ''}`;
                    button.dataset.sortKey = item.key;
                    button.textContent = `${item.label} ${sortKey === item.key ? (sortDirection === 'asc' ? '▲' : '▼') : '↕'}`;
                    th.appendChild(button);
                    headerRow.appendChild(th);
                });
                modalHead.replaceChildren(headerRow);

                const fragment = document.createDocumentFragment();
                rows.forEach((row, index) => {
                    const tr = document.createElement('tr');
                    tr.className = `border-t border-gray-200 ${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}`;
                    modalColumns.forEach((item) => {
                        const td = document.createElement('td');
                        td.className = `px-3 py-2 ${item.align === 'right' ? 'text-right' : 'text-left'} ${item.className || ''}`;
                        const rawValue = row[item.key];
                        const displayValue = item.displayKey ? row[item.displayKey] : (item.format ? item.format(rawValue) : (rawValue ?? '—'));
                        let content;
                        if (item.badgeClassKey) {
                            content = document.createElement('span');
                            content.className = `inline-flex rounded-md border px-2 py-1 text-xs font-semibold ${row[item.badgeClassKey] || ''}`;
                            content.textContent = displayValue;
                        } else if (item.linkKey && row[item.linkKey]) {
                            content = document.createElement('a');
                            content.href = row[item.linkKey];
                            content.className = 'font-medium text-indigo-600 hover:text-indigo-900 hover:underline';
                            content.textContent = displayValue;
                        } else {
                            content = document.createTextNode(displayValue);
                        }
                        td.appendChild(content);
                        tr.appendChild(td);
                    });
                    fragment.appendChild(tr);
                });
                if (rows.length === 0) {
                    const tr = document.createElement('tr');
                    const td = document.createElement('td');
                    td.colSpan = modalColumns.length;
                    td.className = 'px-4 py-10 text-center text-gray-500';
                    td.textContent = 'Дані відсутні.';
                    tr.appendChild(td);
                    fragment.appendChild(tr);
                }
                modalBody.replaceChildren(fragment);
                modalCount.textContent = `Усього записів: ${integer(rows.length)}`;
            }

            function openAnalyticsModal(type, status = '') {
                if (!modal) return;
                if (type === 'debtors') {
                    modalTitle.textContent = 'Боржники за замовленнями — повна таблиця';
                    modalRows = analyticsData.debtors;
                    modalColumns = debtorColumns;
                    sortKey = showFinance ? 'debt_total' : 'orders_count';
                    sortDirection = 'desc';
                } else if (type === 'investors') {
                    modalTitle.textContent = 'Інвестори — поточні переплати — повна таблиця';
                    modalRows = analyticsData.investors;
                    modalColumns = investorColumns;
                    sortKey = showFinance ? 'overpayment_total' : 'client';
                    sortDirection = 'desc';
                } else {
                    const byPayment = type === 'payment-status';
                    const statusKey = byPayment ? 'payment_status' : 'order_status';
                    const labelKey = byPayment ? 'payment_status_label' : 'order_status_label';
                    modalRows = analyticsData.orders.filter((row) => row[statusKey] === status);
                    modalColumns = orderColumns;
                    modalTitle.textContent = `${byPayment ? 'Замовлення зі статусом оплати' : 'Замовлення зі статусом'} «${modalRows[0]?.[labelKey] || status}»`;
                    sortKey = 'updated_at';
                    sortDirection = 'desc';
                }
                renderModalTable();
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
                modalClose?.focus();
            }

            function closeAnalyticsModal() {
                if (!modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }

            document.querySelectorAll('[data-analytics-popup]').forEach((trigger) => {
                const open = () => openAnalyticsModal(trigger.dataset.analyticsPopup, trigger.dataset.status || '');
                trigger.addEventListener('click', (event) => {
                    if (event.target.closest('a, button')) return;
                    open();
                });
                trigger.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        open();
                    }
                });
            });
            modalHead?.addEventListener('click', (event) => {
                const button = event.target.closest('[data-sort-key]');
                if (!button) return;
                if (sortKey === button.dataset.sortKey) {
                    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    sortKey = button.dataset.sortKey;
                    sortDirection = 'asc';
                }
                renderModalTable();
            });
            modalClose?.addEventListener('click', closeAnalyticsModal);
            modal?.addEventListener('click', (event) => {
                if (event.target === modal) closeAnalyticsModal();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) closeAnalyticsModal();
            });
        })();
    </script>
</x-app-layout>
