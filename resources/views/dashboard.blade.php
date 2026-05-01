<x-app-layout>
    @section('title', __('Дашборд'))

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Дашборд</h2>
    </x-slot>

    @php
        $period = $filters['period'] ?? 'mtd';
        $selectedClientIds = collect($filters['client_id'] ?? [])->map(static fn ($value) => (int) $value)->all();
        $selectedClientNames = $clients
            ->filter(fn ($client) => in_array((int) $client->id, $selectedClientIds, true))
            ->pluck('name')
            ->values()
            ->all();
        $dashboardPermissions = $dashboardPermissions ?? [];
        $showKpi = (bool) ($dashboardPermissions['show_kpi'] ?? false);
        $showCharts = (bool) ($dashboardPermissions['show_charts'] ?? false);
        $showTables = (bool) ($dashboardPermissions['show_tables'] ?? false);
        $showFinance = (bool) ($dashboardPermissions['show_finance'] ?? false);
        $canOpenProposal = (bool) ($dashboardPermissions['can_open_proposal'] ?? false);
        $formatMoney = static fn ($value) => number_format((float) $value, 2, '.', ' ');
        $formatPercent = static fn ($value) => number_format((float) $value, 2, '.', ' ').'%';
        $materialsTableTitle = $showFinance ? 'Топ матеріалів за прибутком' : 'Топ матеріалів';
        $servicesTableTitle = $showFinance ? 'Топ послуг за прибутком' : 'Топ послуг';
        $clientsTableTitle = $showFinance ? 'Топ замовників за прибутком' : 'Топ замовників';
        $productTypesTableTitle = $showFinance ? 'Топ типів виробу за прибутком' : 'Топ типів виробу';
        $topProposalsTableTitle = $showFinance ? 'Топ прибуткових заявок' : 'Топ заявок';

        $primaryKpiCards = [
            [
                'label' => 'Кількість заявок',
                'value' => number_format((int) ($kpi['proposal_count'] ?? 0), 0, '.', ' '),
                'help' => 'Показує загальну кількість активних заявок за обраний період та з урахуванням вибраного замовника.',
            ],
            [
                'label' => 'Загальна сума (грн)',
                'value' => $formatMoney($kpi['total_revenue'] ?? 0),
                'help' => 'Сумарна вартість усіх активних заявок, що потрапили у вибірку.',
            ],
            [
                'label' => 'Середній чек (грн)',
                'value' => $formatMoney($kpi['average_check'] ?? 0),
                'help' => 'Середнє значення вартості однієї заявки у вибраному періоді.',
            ],
            [
                'label' => 'Медіанний чек (грн)',
                'value' => $formatMoney($kpi['median_check'] ?? 0),
                'help' => 'Медіанна вартість заявки: половина заявок має суму нижче цього значення, половина вище.',
            ],
            [
                'label' => 'Унікальні замовники',
                'value' => number_format((int) ($kpi['unique_clients'] ?? 0), 0, '.', ' '),
                'help' => 'Кількість різних замовників у вибірці. Облік ведеться через прив’язаний запис замовника.',
            ],
            [
                'label' => 'Заявки з мінімумом',
                'value' => number_format((int) ($kpi['with_minimum'] ?? 0), 0, '.', ' '),
                'help' => 'Кількість заявок, у яких застосовано мінімальну вартість замовлення або виробу.',
            ],
        ];

        $adminOnlyKpiCards = [
            [
                'label' => 'Загальна собівартість (грн)',
                'value' => $formatMoney($kpi['total_purchase_cost'] ?? 0),
                'help' => 'Сумарна розрахункова собівартість усіх заявок за поточними фільтрами.',
            ],
            [
                'label' => 'Валовий прибуток (грн)',
                'value' => $formatMoney($kpi['gross_profit'] ?? 0),
                'help' => 'Різниця між загальною вартістю заявок та їх розрахунковою собівартістю.',
            ],
            [
                'label' => 'Маржинальність (%)',
                'value' => $formatPercent($kpi['margin_percent'] ?? 0),
                'help' => 'Показує частку валового прибутку у загальній вартості заявок.',
            ],
            [
                'label' => 'Середній прибуток (грн)',
                'value' => $formatMoney($kpi['average_profit'] ?? 0),
                'help' => 'Середній валовий прибуток на одну заявку у вибраному періоді.',
            ],
            [
                'label' => 'Нульовий прибуток',
                'value' => number_format((int) ($kpi['break_even_count'] ?? 0), 0, '.', ' '),
                'help' => 'Кількість заявок, у яких прибуток дорівнює нулю.',
            ],
            [
                'label' => 'Збиткові заявки',
                'value' => number_format((int) ($kpi['loss_count'] ?? 0), 0, '.', ' '),
                'help' => 'Кількість заявок, у яких розрахункова собівартість перевищує вартість заявки.',
            ],
        ];

        $kpiCards = array_merge($showKpi ? $primaryKpiCards : [], $showFinance ? $adminOnlyKpiCards : []);

        $chartCards = [
            [
                'id' => 'orders',
                'canvas' => 'chartOrders',
                'title' => 'Динаміка: кількість заявок',
                'help' => 'Показує, як змінювалась кількість заявок у часі.',
                'unit' => 'кількість',
                'tooltip_unit' => 'заявок',
            ],
            [
                'id' => 'revenue',
                'canvas' => 'chartRevenue',
                'title' => 'Динаміка: сума заявок (грн)',
                'help' => 'Показує зміну загальної суми заявок по датах.',
                'unit' => 'грн',
                'tooltip_unit' => 'грн',
            ],
            [
                'id' => 'avg',
                'canvas' => 'chartAvg',
                'title' => 'Динаміка: середній чек (грн)',
                'help' => 'Показує, як змінювався середній чек заявки у часі.',
                'unit' => 'грн',
                'tooltip_unit' => 'грн',
            ],
            [
                'id' => 'purchase',
                'canvas' => 'chartPurchase',
                'title' => 'Динаміка: собівартість (грн)',
                'help' => 'Показує зміну сумарної розрахункової собівартості заявок по датах.',
                'unit' => 'грн',
                'tooltip_unit' => 'грн',
                'admin_only' => true,
            ],
            [
                'id' => 'profit',
                'canvas' => 'chartProfit',
                'title' => 'Динаміка: валовий прибуток (грн)',
                'help' => 'Показує, як змінювався валовий прибуток заявок у часі.',
                'unit' => 'грн',
                'tooltip_unit' => 'грн',
                'admin_only' => true,
            ],
            [
                'id' => 'margin',
                'canvas' => 'chartMargin',
                'title' => 'Динаміка: маржинальність (%)',
                'help' => 'Показує, як змінювався відсоток маржинальності заявок у часі.',
                'unit' => '%',
                'tooltip_unit' => '%',
                'admin_only' => true,
            ],
        ];
        $chartCards = array_values(array_filter($chartCards, static function ($chart) use ($showCharts, $showFinance) {
            return ($chart['admin_only'] ?? false) ? $showFinance : $showCharts;
        }));
    @endphp

    <style>
        .dashboard-shell {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            align-items: stretch;
        }

        @media (min-width: 1280px) {
            .dashboard-shell {
                flex-direction: row;
                align-items: flex-start;
            }
        }

        .dashboard-filters {
            width: 100%;
            flex: 0 0 auto;
        }

        @media (min-width: 1280px) {
            .dashboard-filters {
                width: 242px;
                position: sticky;
                top: 1.5rem;
            }
        }

        .dashboard-content {
            width: 100%;
            min-width: 0;
        }

        @media (min-width: 1280px) {
            .dashboard-content {
                max-width: 1550px;
            }
        }

        .dashboard-panel {
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .dashboard-help-target {
            position: relative;
        }

        .dashboard-help-target:hover {
            z-index: 60;
        }

        .dashboard-help-tooltip {
            position: absolute;
            left: 1rem;
            right: 1rem;
            bottom: calc(100% - 0.25rem);
            z-index: 120;
            display: none;
            padding: 0.75rem 0.875rem;
            border: 1px solid #d1d5db;
            border-radius: 0.75rem;
            background: rgba(17, 24, 39, 0.95);
            color: #fff;
            font-size: 0.8125rem;
            line-height: 1.35;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.18);
        }

        .dashboard-help-tooltip.is-visible {
            display: block;
        }

        .dashboard-chart-card {
            cursor: pointer;
        }

        .dashboard-chart-card:hover,
        .dashboard-kpi-card:hover,
        .dashboard-panel:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
        }

        .dashboard-chart-modal {
            position: fixed;
            inset: 0;
            z-index: 70;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, 0.55);
        }

        .dashboard-chart-modal.is-open {
            display: flex;
        }

        .dashboard-chart-modal-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            width: 1200px;
            max-width: calc(100vw - 2rem);
            height: 600px;
            max-height: calc(100vh - 2rem);
            padding: 1rem 1rem 0.75rem;
            border-radius: 1rem;
            border: 1px solid #d1d5db;
            background: #fff;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
        }

        .dashboard-chart-modal-tooltip {
            position: absolute;
            pointer-events: none;
            z-index: 5;
            display: none;
            min-width: 110px;
            padding: 0.5rem 0.625rem;
            border-radius: 0.75rem;
            background: rgba(17, 24, 39, 0.96);
            color: #fff;
            font-size: 0.8125rem;
            line-height: 1.35;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.2);
        }

        .dashboard-chart-modal-tooltip.is-visible {
            display: block;
        }

        .dashboard-panel-title {
            background-color: #f9fafb;
        }

        .dashboard-chart-modal-body {
            position: relative;
            flex: 1 1 auto;
            min-height: 0;
            padding: 0 0 0.75rem;
            overflow: visible;
        }

        .dashboard-table-panel {
            overflow: visible;
        }

        .dashboard-chart-modal-body canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
        }

        .dashboard-kpi-grid,
        .dashboard-chart-grid,
        .dashboard-two-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: minmax(0, 1fr);
        }

        @media (min-width: 900px) {
            .dashboard-two-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1100px) {
            .dashboard-kpi-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .dashboard-chart-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1450px) {
            .dashboard-kpi-grid {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }
        }

        .dashboard-client-dropdown {
            position: relative;
        }

        .dashboard-client-dropdown-panel {
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

        .dashboard-client-dropdown.is-open .dashboard-client-dropdown-panel {
            display: block;
        }

        .dashboard-client-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.25rem;
            font-size: 0.875rem;
            color: #111827;
        }

        .dashboard-client-option input {
            flex: 0 0 auto;
        }
    </style>

    <div class="py-8">
        <div class="max-w-[1800px] mx-auto px-6 sm:px-8 lg:px-12">
            <div class="dashboard-shell">
                <aside class="dashboard-filters dashboard-panel bg-white border border-gray-200 rounded-lg shadow-sm p-4 h-fit">
                    <form method="GET" action="{{ route('dashboard') }}" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Період</label>
                            <select id="dashboardPeriodSelect" name="period" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                <option value="all" @selected($period === 'all')>За весь період</option>
                                <option value="ytd" @selected($period === 'ytd')>З початку поточного року</option>
                                <option value="mtd" @selected($period === 'mtd')>З початку поточного місяця</option>
                                <option value="wtd" @selected($period === 'wtd')>З початку поточного тижня</option>
                                <option value="custom" @selected($period === 'custom')>Кастомний період</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Від</label>
                                <input id="dashboardFromDate" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">До</label>
                                <input id="dashboardToDate" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Замовник</label>
                            <div id="dashboardClientDropdown" class="dashboard-client-dropdown">
                                <button
                                    id="dashboardClientDropdownToggle"
                                    type="button"
                                    class="w-full border border-gray-300 rounded-md shadow-sm text-sm bg-white px-3 py-2 text-left text-gray-700"
                                >
                                    <span id="dashboardClientDropdownLabel">
                                        @if(count($selectedClientNames) === 0)
                                            Усі замовники
                                        @elseif(count($selectedClientNames) === 1)
                                            {{ $selectedClientNames[0] }}
                                        @else
                                            Обрано: {{ count($selectedClientNames) }}
                                        @endif
                                    </span>
                                </button>
                                <div class="dashboard-client-dropdown-panel">
                                    @foreach($clients as $client)
                                        <label class="dashboard-client-option">
                                            <input
                                                type="checkbox"
                                                name="client_id[]"
                                                value="{{ $client->id }}"
                                                @checked(in_array((int) $client->id, $selectedClientIds, true))
                                            >
                                            <span>{{ $client->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        @if(!empty($periodError))
                            <div class="text-xs text-red-600">{{ $periodError }}</div>
                        @endif

                        <div class="pt-2 flex items-center gap-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md text-sm font-semibold text-white hover:bg-gray-700">
                                Застосувати
                            </button>
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                                Скинути
                            </a>
                        </div>
                    </form>
                </aside>

                <section class="dashboard-content space-y-6 min-w-0">
                    <div class="dashboard-kpi-grid">
                        @foreach($kpiCards as $card)
                            <div class="dashboard-help-target dashboard-panel dashboard-kpi-card bg-white border border-gray-200 rounded-lg shadow-sm p-4 min-h-[110px]" data-help="{{ $card['help'] }}">
                                <div class="text-xs uppercase tracking-wide text-gray-500">{{ $card['label'] }}</div>
                                <div class="mt-2 text-xl font-semibold text-gray-900">{{ $card['value'] }}</div>
                                <div class="dashboard-help-tooltip"></div>
                            </div>
                        @endforeach
                    </div>

                    <div class="dashboard-chart-grid">
                        @foreach($chartCards as $chart)
                            <div
                                class="dashboard-help-target dashboard-panel dashboard-chart-card bg-white border border-gray-200 rounded-lg shadow-sm p-4"
                                data-chart-id="{{ $chart['id'] }}"
                                data-help="{{ $chart['help'] }}"
                            >
                                <div class="text-sm font-semibold text-gray-800 mb-3">{{ $chart['title'] }}</div>
                                <canvas id="{{ $chart['canvas'] }}" height="130"></canvas>
                                <div class="dashboard-help-tooltip"></div>
                            </div>
                        @endforeach
                    </div>

                    @if($showTables)
                    <div class="dashboard-two-grid">
                        <div class="dashboard-help-target dashboard-panel dashboard-table-panel bg-white border border-gray-200 rounded-lg shadow-sm" data-help="Показує матеріали, які дали найбільший прибуток у складі заявок за поточними фільтрами.">
                            <div class="px-4 py-3 dashboard-panel-title border-b font-semibold text-gray-800">{{ $materialsTableTitle }}</div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead style="background-color: #FCEEDF;">
                                        <tr>
                                            <th class="px-3 py-2 text-left border-b">Матеріал</th>
                                            <th class="px-3 py-2 text-right border-b">К-сть</th>
                                            <th class="px-3 py-2 text-right border-b">Вартість</th>
                                            @if($showFinance)
                                                <th class="px-3 py-2 text-right border-b">Собівартість</th>
                                                <th class="px-3 py-2 text-right border-b">Прибуток</th>
                                                <th class="px-3 py-2 text-right border-b">Маржа %</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($topMaterials as $row)
                                            <tr>
                                                <td class="px-3 py-2 border-b">{{ $row['name'] }}</td>
                                                <td class="px-3 py-2 border-b text-right">{{ number_format((int) $row['count'], 0, '.', ' ') }}</td>
                                                <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['sum']) }}</td>
                                                @if($showFinance)
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['purchase_sum']) }}</td>
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['profit_sum']) }}</td>
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatPercent($row['margin_percent']) }}</td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr><td colspan="{{ $showFinance ? 6 : 3 }}" class="px-3 py-6 text-center text-gray-500">Немає даних</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="dashboard-help-tooltip"></div>
                        </div>

                        <div class="dashboard-help-target dashboard-panel dashboard-table-panel bg-white border border-gray-200 rounded-lg shadow-sm" data-help="Показує послуги, які дали найбільший прибуток у поточній вибірці.">
                            <div class="px-4 py-3 dashboard-panel-title border-b font-semibold text-gray-800">{{ $servicesTableTitle }}</div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead style="background-color: #FCEEDF;">
                                        <tr>
                                            <th class="px-3 py-2 text-left border-b">Послуга</th>
                                            <th class="px-3 py-2 text-right border-b">К-сть</th>
                                            <th class="px-3 py-2 text-right border-b">Вартість</th>
                                            @if($showFinance)
                                                <th class="px-3 py-2 text-right border-b">Собівартість</th>
                                                <th class="px-3 py-2 text-right border-b">Прибуток</th>
                                                <th class="px-3 py-2 text-right border-b">Маржа %</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($topServices as $row)
                                            <tr>
                                                <td class="px-3 py-2 border-b">{{ $row['name'] }}</td>
                                                <td class="px-3 py-2 border-b text-right">{{ number_format((int) $row['count'], 0, '.', ' ') }}</td>
                                                <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['sum']) }}</td>
                                                @if($showFinance)
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['purchase_sum']) }}</td>
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['profit_sum']) }}</td>
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatPercent($row['margin_percent']) }}</td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr><td colspan="{{ $showFinance ? 6 : 3 }}" class="px-3 py-6 text-center text-gray-500">Немає даних</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="dashboard-help-tooltip"></div>
                        </div>
                    </div>

                    <div class="dashboard-two-grid">
                        <div class="dashboard-help-target dashboard-panel dashboard-table-panel bg-white border border-gray-200 rounded-lg shadow-sm" data-help="Показує замовників, заявки яких дали найбільший валовий прибуток у поточній вибірці.">
                            <div class="px-4 py-3 dashboard-panel-title border-b font-semibold text-gray-800">{{ $clientsTableTitle }}</div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead style="background-color: #FCEEDF;">
                                        <tr>
                                            <th class="px-3 py-2 text-left border-b">Замовник</th>
                                            <th class="px-3 py-2 text-right border-b">К-сть</th>
                                            <th class="px-3 py-2 text-right border-b">Вартість</th>
                                            @if($showFinance)
                                                <th class="px-3 py-2 text-right border-b">Собівартість</th>
                                                <th class="px-3 py-2 text-right border-b">Прибуток</th>
                                                <th class="px-3 py-2 text-right border-b">Маржа %</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($topClients as $row)
                                            <tr>
                                                <td class="px-3 py-2 border-b">{{ $row['name'] ?: '—' }}</td>
                                                <td class="px-3 py-2 border-b text-right">{{ number_format((int) $row['count'], 0, '.', ' ') }}</td>
                                                <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['sum']) }}</td>
                                                @if($showFinance)
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['purchase_sum']) }}</td>
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['profit_sum']) }}</td>
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatPercent($row['margin_percent']) }}</td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr><td colspan="{{ $showFinance ? 6 : 3 }}" class="px-3 py-6 text-center text-gray-500">Немає даних</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="dashboard-help-tooltip"></div>
                        </div>

                        <div class="space-y-4">
                            <div class="dashboard-help-target dashboard-panel dashboard-table-panel bg-white border border-gray-200 rounded-lg shadow-sm" data-help="Показує типи виробів, які дали найбільший прибуток у поточній вибірці.">
                                <div class="px-4 py-3 dashboard-panel-title border-b font-semibold text-gray-800">{{ $productTypesTableTitle }}</div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead style="background-color: #FCEEDF;">
                                            <tr>
                                                <th class="px-3 py-2 text-left border-b">Тип виробу</th>
                                                <th class="px-3 py-2 text-right border-b">К-сть</th>
                                                <th class="px-3 py-2 text-right border-b">Вартість</th>
                                                @if($showFinance)
                                                    <th class="px-3 py-2 text-right border-b">Собівартість</th>
                                                    <th class="px-3 py-2 text-right border-b">Прибуток</th>
                                                    <th class="px-3 py-2 text-right border-b">Маржа %</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topProductTypes as $row)
                                                <tr>
                                                    <td class="px-3 py-2 border-b">{{ $row['name'] }}</td>
                                                    <td class="px-3 py-2 border-b text-right">{{ number_format((int) $row['count'], 0, '.', ' ') }}</td>
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['sum']) }}</td>
                                                    @if($showFinance)
                                                        <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['purchase_sum']) }}</td>
                                                        <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['profit_sum']) }}</td>
                                                        <td class="px-3 py-2 border-b text-right">{{ $formatPercent($row['margin_percent']) }}</td>
                                                    @endif
                                                </tr>
                                            @empty
                                                <tr><td colspan="{{ $showFinance ? 6 : 3 }}" class="px-3 py-6 text-center text-gray-500">Немає даних</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="dashboard-help-tooltip"></div>
                            </div>

                            @if($showFinance)
                            <div class="dashboard-help-target dashboard-panel dashboard-table-panel bg-white border border-gray-200 rounded-lg shadow-sm" data-help="Показує менеджерів, чиї заявки дали найбільший валовий прибуток за обраний період.">
                                <div class="px-4 py-3 dashboard-panel-title border-b font-semibold text-gray-800">Топ менеджерів за прибутком</div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead style="background-color: #FCEEDF;">
                                            <tr>
                                                <th class="px-3 py-2 text-left border-b">Користувач</th>
                                                <th class="px-3 py-2 text-right border-b">К-сть</th>
                                                <th class="px-3 py-2 text-right border-b">Вартість</th>
                                                <th class="px-3 py-2 text-right border-b">Собівартість</th>
                                                <th class="px-3 py-2 text-right border-b">Прибуток</th>
                                                <th class="px-3 py-2 text-right border-b">Маржа %</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topManagers as $row)
                                                <tr>
                                                    <td class="px-3 py-2 border-b">{{ $row['name'] ?: '—' }}</td>
                                                    <td class="px-3 py-2 border-b text-right">{{ number_format((int) $row['count'], 0, '.', ' ') }}</td>
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['sum']) }}</td>
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['purchase_sum']) }}</td>
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['profit_sum']) }}</td>
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatPercent($row['margin_percent']) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">Немає даних</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="dashboard-help-tooltip"></div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="dashboard-two-grid">
                        <div class="dashboard-help-target dashboard-panel dashboard-table-panel bg-white border border-gray-200 rounded-lg shadow-sm" data-help="Показує заявки з найбільшим валовим прибутком у поточній вибірці.">
                            <div class="px-4 py-3 dashboard-panel-title border-b font-semibold text-gray-800">{{ $topProposalsTableTitle }}</div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead style="background-color: #FCEEDF;">
                                        <tr>
                                            <th class="px-3 py-2 text-left border-b">Заявка</th>
                                            <th class="px-3 py-2 text-left border-b">Замовник</th>
                                            <th class="px-3 py-2 text-right border-b">Вартість</th>
                                            @if($showFinance)
                                                <th class="px-3 py-2 text-right border-b">Собівартість</th>
                                                <th class="px-3 py-2 text-right border-b">Прибуток</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($topProfitableProposals as $row)
                                            <tr>
                                                <td class="px-3 py-2 border-b">
                                                    @if($canOpenProposal)
                                                        <a href="{{ route('orders.proposals.show', $row['proposal_id']) }}" class="text-blue-700 hover:underline">
                                                            {{ $row['proposal_number'] }}
                                                        </a>
                                                    @else
                                                        {{ $row['proposal_number'] }}
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 border-b">{{ $row['client_name'] }}</td>
                                                <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['total_cost']) }}</td>
                                                @if($showFinance)
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['purchase_cost']) }}</td>
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['gross_profit']) }}</td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr><td colspan="{{ $showFinance ? 5 : 3 }}" class="px-3 py-6 text-center text-gray-500">Немає даних</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="dashboard-help-tooltip"></div>
                        </div>

                        <div class="dashboard-help-target dashboard-panel dashboard-table-panel bg-white border border-gray-200 rounded-lg shadow-sm" data-help="Показує збиткові заявки, де собівартість перевищує вартість.">
                            <div class="px-4 py-3 dashboard-panel-title border-b font-semibold text-gray-800">Збиткові заявки</div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead style="background-color: #FCEEDF;">
                                        <tr>
                                            <th class="px-3 py-2 text-left border-b">Заявка</th>
                                            <th class="px-3 py-2 text-left border-b">Замовник</th>
                                            <th class="px-3 py-2 text-right border-b">Вартість</th>
                                            @if($showFinance)
                                                <th class="px-3 py-2 text-right border-b">Собівартість</th>
                                                <th class="px-3 py-2 text-right border-b">Прибуток</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($topLossProposals as $row)
                                            <tr>
                                                <td class="px-3 py-2 border-b">
                                                    @if($canOpenProposal)
                                                        <a href="{{ route('orders.proposals.show', $row['proposal_id']) }}" class="text-blue-700 hover:underline">
                                                            {{ $row['proposal_number'] }}
                                                        </a>
                                                    @else
                                                        {{ $row['proposal_number'] }}
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 border-b">{{ $row['client_name'] }}</td>
                                                <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['total_cost']) }}</td>
                                                @if($showFinance)
                                                    <td class="px-3 py-2 border-b text-right">{{ $formatMoney($row['purchase_cost']) }}</td>
                                                    <td class="px-3 py-2 border-b text-right text-red-600">{{ $formatMoney($row['gross_profit']) }}</td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr><td colspan="{{ $showFinance ? 5 : 3 }}" class="px-3 py-6 text-center text-gray-500">Немає даних</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="dashboard-help-tooltip"></div>
                        </div>
                    </div>
                    @endif
                </section>
            </div>
        </div>
    </div>

    <div id="dashboardChartModal" class="dashboard-chart-modal">
        <div class="dashboard-chart-modal-panel">
            <div class="flex items-center justify-between gap-4 mb-3">
                <div>
                    <div id="dashboardChartModalTitle" class="text-lg font-semibold text-gray-900"></div>
                    <div id="dashboardChartModalSubtitle" class="text-sm text-gray-500">Натисніть `Esc` або кнопку закриття, щоб повернутись.</div>
                </div>
                <button
                    type="button"
                    id="dashboardChartModalClose"
                    class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50"
                >
                    Закрити
                </button>
            </div>
            <div class="dashboard-chart-modal-body">
                <canvas id="dashboardChartModalCanvas" class="w-full h-full"></canvas>
                <div id="dashboardChartModalTooltip" class="dashboard-chart-modal-tooltip"></div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const series = @json($series, JSON_UNESCAPED_UNICODE);
            const periodSelect = document.getElementById('dashboardPeriodSelect');
            const fromInput = document.getElementById('dashboardFromDate');
            const toInput = document.getElementById('dashboardToDate');
            const clientDropdown = document.getElementById('dashboardClientDropdown');
            const clientDropdownToggle = document.getElementById('dashboardClientDropdownToggle');
            const clientDropdownLabel = document.getElementById('dashboardClientDropdownLabel');
            const clientCheckboxes = clientDropdown ? clientDropdown.querySelectorAll('input[type="checkbox"][name="client_id[]"]') : [];
            const chartConfigs = {
                orders: {
                    canvas: 'chartOrders',
                    title: 'Динаміка: кількість заявок',
                    unit: 'кількість',
                    tooltipUnit: 'заявок',
                    color: '#2563EB',
                    data: Array.isArray(series.orders) ? series.orders : [],
                },
                revenue: {
                    canvas: 'chartRevenue',
                    title: 'Динаміка: сума заявок (грн)',
                    unit: 'грн',
                    tooltipUnit: 'грн',
                    color: '#059669',
                    data: Array.isArray(series.revenue) ? series.revenue : [],
                },
                purchase: {
                    canvas: 'chartPurchase',
                    title: 'Динаміка: собівартість (грн)',
                    unit: 'грн',
                    tooltipUnit: 'грн',
                    color: '#EA580C',
                    data: Array.isArray(series.purchase) ? series.purchase : [],
                },
                profit: {
                    canvas: 'chartProfit',
                    title: 'Динаміка: валовий прибуток (грн)',
                    unit: 'грн',
                    tooltipUnit: 'грн',
                    color: '#DC2626',
                    data: Array.isArray(series.profit) ? series.profit : [],
                },
                avg: {
                    canvas: 'chartAvg',
                    title: 'Динаміка: середній чек (грн)',
                    unit: 'грн',
                    tooltipUnit: 'грн',
                    color: '#7C3AED',
                    data: Array.isArray(series.avg) ? series.avg : [],
                },
                margin: {
                    canvas: 'chartMargin',
                    title: 'Динаміка: маржинальність (%)',
                    unit: '%',
                    tooltipUnit: '%',
                    color: '#0F766E',
                    data: Array.isArray(series.margin) ? series.margin : [],
                },
            };
            const labels = Array.isArray(series.labels) ? series.labels : [];

            function syncCustomPeriod() {
                if (!periodSelect) {
                    return;
                }

                if ((fromInput && fromInput.value) || (toInput && toInput.value)) {
                    periodSelect.value = 'custom';
                }
            }

            if (fromInput) {
                fromInput.addEventListener('change', syncCustomPeriod);
                fromInput.addEventListener('input', syncCustomPeriod);
            }

            if (toInput) {
                toInput.addEventListener('change', syncCustomPeriod);
                toInput.addEventListener('input', syncCustomPeriod);
            }

            function syncClientDropdownLabel() {
                if (!clientDropdownLabel) {
                    return;
                }

                const checked = Array.from(clientCheckboxes).filter((checkbox) => checkbox.checked);
                if (checked.length === 0) {
                    clientDropdownLabel.textContent = 'Усі замовники';
                    return;
                }

                if (checked.length === 1) {
                    const optionLabel = checked[0].closest('label');
                    clientDropdownLabel.textContent = optionLabel ? optionLabel.textContent.trim() : '1 замовник';
                    return;
                }

                clientDropdownLabel.textContent = `Обрано: ${checked.length}`;
            }

            if (clientDropdown && clientDropdownToggle) {
                syncClientDropdownLabel();

                clientDropdownToggle.addEventListener('click', () => {
                    clientDropdown.classList.toggle('is-open');
                });

                clientCheckboxes.forEach((checkbox) => {
                    checkbox.addEventListener('change', syncClientDropdownLabel);
                });

                document.addEventListener('click', (event) => {
                    if (!clientDropdown.contains(event.target)) {
                        clientDropdown.classList.remove('is-open');
                    }
                });
            }

            function formatValue(value, unit) {
                const numeric = Number(value || 0);
                if (unit === 'грн') {
                    return `${numeric.toLocaleString('uk-UA', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} грн`;
                }
                if (unit === '%') {
                    return `${numeric.toLocaleString('uk-UA', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} %`;
                }
                return `${numeric.toLocaleString('uk-UA')} ${unit}`;
            }

            function drawLineChart(canvasId, data, color, options = {}) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) {
                    return { points: [], draw: () => {} };
                }

                const ctx = canvas.getContext('2d');
                const hasData = Array.isArray(data) && data.length > 0;
                const dpr = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();
                const width = rect.width || canvas.width || 300;
                const height = rect.height || canvas.height || 150;

                canvas.width = Math.floor(width * dpr);
                canvas.height = Math.floor(height * dpr);
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.scale(dpr, dpr);

                const w = width;
                const h = height;
                const padding = options.padding || { top: 10, right: 12, bottom: 18, left: 10 };
                const chartW = Math.max(w - padding.left - padding.right, 20);
                const chartH = Math.max(h - padding.top - padding.bottom, 20);

                if (!hasData) {
                    ctx.clearRect(0, 0, w, h);
                    ctx.fillStyle = '#94A3B8';
                    ctx.font = '12px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.fillText('Немає даних', w / 2, h / 2);
                    return { points: [], draw: () => {} };
                }

                const min = Math.min(...data, 0);
                const max = Math.max(...data, 1);
                const range = max - min || 1;
                const points = data.map((value, index) => {
                    const x = padding.left + (chartW * (data.length === 1 ? 0.5 : index / (data.length - 1)));
                    const y = padding.top + chartH - ((value - min) / range) * chartH;
                    return { x, y, value, index };
                });

                function draw(activeIndex = null) {
                    ctx.clearRect(0, 0, w, h);

                    ctx.strokeStyle = '#E5E7EB';
                    ctx.lineWidth = 1;
                    for (let i = 0; i < 4; i++) {
                        const y = padding.top + (chartH / 3) * i;
                        ctx.beginPath();
                        ctx.moveTo(padding.left, y);
                        ctx.lineTo(padding.left + chartW, y);
                        ctx.stroke();
                    }

                    if (options.axes) {
                        ctx.strokeStyle = '#CBD5E1';
                        ctx.beginPath();
                        ctx.moveTo(padding.left, padding.top);
                        ctx.lineTo(padding.left, padding.top + chartH);
                        ctx.lineTo(padding.left + chartW, padding.top + chartH);
                        ctx.stroke();

                        ctx.fillStyle = '#64748B';
                        ctx.font = '12px sans-serif';
                        ctx.textAlign = 'right';
                        for (let i = 0; i < 5; i++) {
                            const ratio = i / 4;
                            const value = max - (range * ratio);
                            const y = padding.top + chartH * ratio;
                            ctx.fillText(Number(value).toLocaleString('uk-UA', { maximumFractionDigits: 2 }), padding.left - 8, y + 4);
                        }

                        ctx.save();
                        ctx.translate(18, padding.top + chartH / 2);
                        ctx.rotate(-Math.PI / 2);
                        ctx.textAlign = 'center';
                        ctx.fillText(options.yAxisLabel || '', 0, 0);
                        ctx.restore();

                        ctx.textAlign = 'center';
                        ctx.fillText('Дата', padding.left + chartW / 2, h - 8);

                        const tickCount = Math.min(labels.length || points.length, 6);
                        for (let i = 0; i < tickCount; i++) {
                            const dataIndex = tickCount === 1 ? 0 : Math.round((points.length - 1) * (i / (tickCount - 1)));
                            const point = points[dataIndex];
                            if (!point) {
                                continue;
                            }
                            ctx.fillText(labels[dataIndex] || '', point.x, padding.top + chartH + 16);
                        }
                    }

                    ctx.strokeStyle = color;
                    ctx.lineWidth = options.axes ? 3 : 2;
                    ctx.beginPath();
                    points.forEach((point, index) => {
                        if (index === 0) {
                            ctx.moveTo(point.x, point.y);
                        } else {
                            ctx.lineTo(point.x, point.y);
                        }
                    });
                    ctx.stroke();

                    points.forEach((point, index) => {
                        const isActive = activeIndex === index;
                        if (isActive || options.axes) {
                            ctx.fillStyle = '#FFFFFF';
                            ctx.beginPath();
                            ctx.arc(point.x, point.y, isActive ? 6 : 4, 0, Math.PI * 2);
                            ctx.fill();
                        }

                        ctx.fillStyle = color;
                        ctx.beginPath();
                        ctx.arc(point.x, point.y, isActive ? 4.5 : (options.axes ? 3 : 0), 0, Math.PI * 2);
                        if (isActive || options.axes) {
                            ctx.fill();
                        }
                    });
                }

                draw();
                return { points, draw };
            }

            Object.values(chartConfigs).forEach((config) => {
                drawLineChart(config.canvas, config.data, config.color);
            });

            const helpTargets = document.querySelectorAll('.dashboard-help-target');
            helpTargets.forEach((target) => {
                const tooltip = target.querySelector('.dashboard-help-tooltip');
                const text = target.getAttribute('data-help');
                if (!tooltip || !text) {
                    return;
                }

                let timer = null;
                tooltip.textContent = text;

                target.addEventListener('mouseenter', () => {
                    timer = window.setTimeout(() => {
                        tooltip.classList.add('is-visible');
                    }, 2000);
                });

                target.addEventListener('mouseleave', () => {
                    if (timer) {
                        window.clearTimeout(timer);
                        timer = null;
                    }
                    tooltip.classList.remove('is-visible');
                });
            });

            const modal = document.getElementById('dashboardChartModal');
            const modalPanel = modal ? modal.querySelector('.dashboard-chart-modal-panel') : null;
            const modalCanvas = document.getElementById('dashboardChartModalCanvas');
            const modalTitle = document.getElementById('dashboardChartModalTitle');
            const modalTooltip = document.getElementById('dashboardChartModalTooltip');
            const closeButton = document.getElementById('dashboardChartModalClose');
            let currentModalChart = null;
            let currentModalConfig = null;

            function hideModalTooltip() {
                if (modalTooltip) {
                    modalTooltip.classList.remove('is-visible');
                }
            }

            function openChartModal(chartId) {
                const config = chartConfigs[chartId];
                if (!modal || !modalCanvas || !config) {
                    return;
                }

                modal.classList.add('is-open');
                if (modalTitle) {
                    modalTitle.textContent = config.title;
                }

                currentModalConfig = config;
                window.requestAnimationFrame(() => {
                    currentModalChart = drawLineChart(
                        'dashboardChartModalCanvas',
                        config.data,
                        config.color,
                        {
                            axes: true,
                            yAxisLabel: config.unit,
                            padding: { top: 24, right: 24, bottom: 64, left: 72 },
                        }
                    );
                });
            }

            document.querySelectorAll('.dashboard-chart-card').forEach((card) => {
                card.addEventListener('click', () => {
                    const chartId = card.getAttribute('data-chart-id');
                    openChartModal(chartId);
                });
            });

            if (modalCanvas) {
                modalCanvas.addEventListener('mousemove', (event) => {
                    if (!currentModalChart || !currentModalConfig || !Array.isArray(currentModalChart.points) || currentModalChart.points.length === 0) {
                        hideModalTooltip();
                        return;
                    }

                    const rect = modalCanvas.getBoundingClientRect();
                    const x = event.clientX - rect.left;
                    const y = event.clientY - rect.top;
                    let nearest = currentModalChart.points[0];
                    let nearestDistance = Math.hypot(nearest.x - x, nearest.y - y);

                    currentModalChart.points.forEach((point) => {
                        const distance = Math.hypot(point.x - x, point.y - y);
                        if (distance < nearestDistance) {
                            nearest = point;
                            nearestDistance = distance;
                        }
                    });

                    currentModalChart.draw(nearest.index);
                    if (modalTooltip && modalPanel) {
                        modalTooltip.innerHTML = `<div>${labels[nearest.index] || ''}</div><div><strong>${formatValue(nearest.value, currentModalConfig.tooltipUnit)}</strong></div>`;
                        modalTooltip.style.left = `${Math.min(nearest.x + 36, modalPanel.clientWidth - 170)}px`;
                        modalTooltip.style.top = `${Math.max(nearest.y + 24, 70)}px`;
                        modalTooltip.classList.add('is-visible');
                    }
                });

                modalCanvas.addEventListener('mouseleave', () => {
                    if (currentModalChart) {
                        currentModalChart.draw();
                    }
                    hideModalTooltip();
                });
            }

            function closeChartModal() {
                if (!modal) {
                    return;
                }
                modal.classList.remove('is-open');
                currentModalChart = null;
                currentModalConfig = null;
                hideModalTooltip();
            }

            if (closeButton) {
                closeButton.addEventListener('click', closeChartModal);
            }

            if (modal) {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeChartModal();
                    }
                });
            }

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal && modal.classList.contains('is-open')) {
                    closeChartModal();
                }
            });
        })();
    </script>
</x-app-layout>
