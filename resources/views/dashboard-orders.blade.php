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
                            <div class="orders-analytics-panel overflow-hidden rounded-lg border border-red-200 bg-white shadow-sm">
                                <div class="border-b border-red-200 bg-rose-50 px-4 py-3">
                                    <div class="font-semibold text-red-900">Боржники за замовленнями</div>
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
                                            @forelse($debtorClients as $debtor)
                                                <tr class="border-t border-gray-200 {{ $loop->odd ? 'bg-white' : 'bg-gray-50' }}">
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

                            <div class="orders-analytics-panel overflow-hidden rounded-lg border border-blue-200 bg-white shadow-sm">
                                <div class="border-b border-blue-200 bg-blue-50 px-4 py-3">
                                    <div class="font-semibold text-blue-900">Інвестори — поточні переплати</div>
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
                                            @forelse($investorClients as $investor)
                                                <tr class="border-t border-gray-200 {{ $loop->odd ? 'bg-white' : 'bg-gray-50' }}">
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
                                        @foreach($statusStats as $status)
                                            <tr class="border-t border-gray-200">
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
                            <div class="border-b bg-gray-50 px-4 py-3 font-semibold text-gray-800">Замовлення у вибраному періоді (до 100 останніх)</div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead style="background-color: #D3D4D4;">
                                        <tr>
                                            <th class="border-b px-3 py-2 text-left">Дата</th>
                                            <th class="border-b px-3 py-2 text-left">Номер замовлення</th>
                                            <th class="border-b px-3 py-2 text-left">Оплата</th>
                                            <th class="border-b px-3 py-2 text-left">Замовник</th>
                                            <th class="border-b px-3 py-2 text-left">Користувач</th>
                                            @if($showFinance)
                                                <th class="border-b px-3 py-2 text-right">Вартість</th>
                                                <th class="border-b px-3 py-2 text-right">Сплачено</th>
                                                <th class="border-b px-3 py-2 text-right">До сплати</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($analyticsOrders as $order)
                                            <tr class="border-t border-gray-200 {{ $loop->odd ? 'bg-gray-50' : 'bg-white' }}">
                                                <td class="px-3 py-2">{{ $formatDate($order['updated_at']) }}</td>
                                                <td class="px-3 py-2 font-medium">
                                                    @if($canOpenOrder)
                                                        <a href="{{ route('orders.show', $order['public_id']) }}" class="text-indigo-600 hover:text-indigo-900">{{ $order['number'] }}</a>
                                                    @else
                                                        {{ $order['number'] }}
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2"><span class="inline-flex rounded-md border px-2 py-1 text-xs font-semibold {{ $order['status_class'] }}">{{ $order['status_label'] }}</span></td>
                                                <td class="px-3 py-2">{{ $order['customer'] }}</td>
                                                <td class="px-3 py-2">{{ $order['user'] }}</td>
                                                @if($showFinance)
                                                    <td class="px-3 py-2 text-right">{{ $formatMoney($order['total_cost']) }}</td>
                                                    <td class="px-3 py-2 text-right">{{ $formatMoney($order['payments_total']) }}</td>
                                                    <td class="px-3 py-2 text-right font-semibold">{{ $formatMoney($order['amount_due']) }}</td>
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
        })();
    </script>
</x-app-layout>
