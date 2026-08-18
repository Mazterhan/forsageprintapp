<x-app-layout>
    @section('title', __('Замовлення'))
    @php
        $ordersPermissions = $ordersPermissions ?? [];
        $formatOrderMoney = static function ($value): string {
            $formatted = number_format((float) $value, 2, '.', ' ');
            return preg_replace('/\.0+$/', '', $formatted) ?? $formatted;
        };
        $formatOrderDate = static fn ($date): string => $date
            ? $date->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i')
            : '';
        $nextDir = fn (string $column): string => ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
        $sortLink = fn (string $column): string => route('orders.index', array_merge(
            request()->query(),
            ['sort' => $column, 'direction' => $nextDir($column)]
        ));
        $paymentStatus = static function ($order): array {
            $paymentsTotal = (float) ($order->linked_payments_total ?? 0);
            $orderTotal = (float) $order->total_cost;

            if ($paymentsTotal <= 0) {
                return ['Не сплачено', 'border-red-300 bg-rose-100 text-red-800'];
            }

            if ($paymentsTotal < $orderTotal) {
                return ['Частково сплачено', 'border-orange-500 bg-yellow-100 text-orange-800'];
            }

            if (abs($paymentsTotal - $orderTotal) < 0.005) {
                return ['Сплачено', 'border-green-300 bg-green-100 text-green-800'];
            }

            return ['Є переплата', 'border-blue-400 bg-teal-100 text-blue-800'];
        };
        $orderFilters = $orderFilters ?? [];
        $orderTableColumnDefinitions = $orderTableColumnDefinitions ?? [];
        $orderTableColumns = $orderTableColumns ?? array_keys($orderTableColumnDefinitions);
        $orderFilterDefinitions = [
            'client_id' => [
                'label' => "Ім'я замовника",
                'options' => collect($availableClients ?? [])->map(
                    static fn ($client): array => ['value' => (string) $client->id, 'label' => $client->name]
                )->values(),
            ],
            'order_status' => [
                'label' => 'Статус замовлення',
                'options' => collect($availableOrderStatuses ?? [])->map(
                    static fn ($label, $value): array => ['value' => (string) $value, 'label' => $label]
                )->values(),
            ],
            'payment_status' => [
                'label' => 'Оплата',
                'options' => collect($availablePaymentStatuses ?? [])->map(
                    static fn ($label, $value): array => ['value' => (string) $value, 'label' => $label]
                )->values(),
            ],
            'user_id' => [
                'label' => 'Користувач',
                'options' => collect($availableUsers ?? [])->map(
                    static fn ($user): array => ['value' => (string) $user->id, 'label' => $user->name]
                )->values(),
            ],
        ];
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Замовлення') }}
                </h2>
                @if($ordersPermissions['calculation'] ?? false)
                    <a href="{{ route('orders.calculation') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        {{ __('Прорахунок замовлення') }}
                    </a>
                @endif
                @if($ordersPermissions['proposals'] ?? false)
                    <a href="{{ route('orders.proposals') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        {{ __('Збережені заявки') }}
                    </a>
                @endif
                @if($ordersPermissions['access'] ?? false)
                    <a href="{{ route('orders.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        {{ __('Створити замовлення') }}
                    </a>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if($ordersPermissions['clients'] ?? false)
                    <a href="{{ route('orders.clients.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        {{ __('Замовники') }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <style>
        .orders-table thead tr {
            background-color: #FCEEDF;
        }

        .order-row {
            transition: background-color 0.5s ease, background-image 0.5s ease;
        }

        .order-row td {
            background: transparent;
        }

        .order-row.row-alt {
            background-color: #F9FAFB;
        }

        .order-row.row-base {
            background-color: #FFFFFF;
        }

        .order-row:hover {
            background-color: #D8F1F2;
            background-image: linear-gradient(90deg, #e9f7f7 0%, #D8F1F2 100%);
        }
    </style>

    @if($ordersPermissions['access'] ?? false)
    <div class="py-12">
        <div
            x-data="ordersTableDisplayEditor({
                definitions: @js($orderTableColumnDefinitions),
                selected: @js($orderTableColumns),
                updateUrl: @js(route('orders.table-columns.update')),
            })"
            @keydown.escape.window="closeEditor()"
            class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12"
        >
            <form method="GET" action="{{ route('orders.index') }}" data-orders-filters class="relative z-30 mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <input type="hidden" name="per_page" value="{{ $perPageRaw }}">

                <div class="flex flex-wrap items-end gap-3">
                    @foreach($orderFilterDefinitions as $filterName => $filterDefinition)
                        @php
                            $selectedFilterValues = array_map('strval', (array) ($orderFilters[$filterName] ?? []));
                            $selectedFilterCount = count($selectedFilterValues);
                        @endphp
                        <div class="min-w-[220px] flex-1">
                            <div class="mb-1 text-sm font-semibold text-gray-700">{{ $filterDefinition['label'] }}</div>
                            @if($filterName === 'client_id')
                                <div data-orders-filter-details data-orders-client-filter class="relative">
                                    <div class="flex h-[42px] items-center rounded-md border border-gray-300 bg-white px-2 shadow-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                                        <input
                                            type="search"
                                            data-orders-client-search
                                            autocomplete="off"
                                            placeholder="Введіть ім'я замовника"
                                            class="h-full min-w-0 flex-1 border-0 bg-transparent px-1 text-sm shadow-none outline-none focus:border-0 focus:ring-0"
                                        >
                                        <span data-orders-filter-count class="ml-2 whitespace-nowrap text-xs font-semibold text-indigo-600">
                                            {{ $selectedFilterCount > 0 ? 'Обрано: '.$selectedFilterCount : '' }}
                                        </span>
                                        <span class="ml-2 text-gray-500">▾</span>
                                    </div>
                                    <div data-orders-filter-panel hidden class="absolute left-0 top-full z-50 mt-1 max-h-64 w-full min-w-[220px] overflow-y-auto rounded-md border border-gray-300 bg-white p-2 shadow-lg">
                            @else
                                <details data-orders-filter-details class="relative">
                                    <summary class="flex h-[42px] cursor-pointer list-none items-center justify-between rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-sm hover:bg-gray-50">
                                        <span data-orders-filter-count>{{ $selectedFilterCount > 0 ? 'Обрано: '.$selectedFilterCount : 'Усі' }}</span>
                                        <span class="text-gray-500">▾</span>
                                    </summary>
                                    <div class="absolute left-0 top-full z-50 mt-1 max-h-64 w-full min-w-[220px] overflow-y-auto rounded-md border border-gray-300 bg-white p-2 shadow-lg">
                            @endif
                                    @forelse($filterDefinition['options'] as $option)
                                        <label
                                            @if($filterName === 'client_id')
                                                data-orders-client-option
                                                data-client-name="{{ $option['label'] }}"
                                            @endif
                                            class="flex cursor-pointer items-center gap-2 rounded px-2 py-2 text-sm text-gray-700 hover:bg-indigo-50"
                                        >
                                            <input
                                                type="checkbox"
                                                name="{{ $filterName }}[]"
                                                value="{{ $option['value'] }}"
                                                @checked(in_array($option['value'], $selectedFilterValues, true))
                                                data-orders-filter-input
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                            >
                                            <span>{{ $option['label'] }}</span>
                                        </label>
                                    @empty
                                        <div class="px-2 py-3 text-sm text-gray-500">Немає доступних значень</div>
                                    @endforelse
                                    @if($filterName === 'client_id')
                                        <div data-orders-client-no-results hidden class="px-2 py-3 text-sm text-gray-500">
                                            Клієнтів не знайдено
                                        </div>
                                    @endif
                                    </div>
                                @if($filterName === 'client_id')
                                    </div>
                                @else
                                    </details>
                                @endif
                        </div>
                    @endforeach

                    <div class="flex h-[42px] items-center gap-2">
                        <a
                            href="{{ route('orders.index', ['sort' => $sort, 'direction' => $direction, 'per_page' => $perPageRaw]) }}"
                            class="inline-flex h-[42px] items-center rounded-md border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Скинути
                        </a>
                    </div>
                </div>
            </form>

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <button
                    type="button"
                    data-orders-table-display-button
                    @click="openEditor()"
                    class="inline-flex h-[42px] items-center rounded-md border border-indigo-300 bg-white px-4 text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-50"
                >
                    Редагувати відображення таблиці
                </button>
                <div class="flex items-center gap-2">
                    <label for="orders-per-page" class="text-sm text-gray-700 whitespace-nowrap">
                        Кількість замовлень на сторінці
                    </label>
                    <select
                        id="orders-per-page"
                        class="border-gray-300 rounded-md text-sm shadow-sm"
                        onchange="window.changeOrdersPerPage(this.value)"
                    >
                        <option value="20" @selected($perPageRaw === '20')>20</option>
                        <option value="50" @selected($perPageRaw === '50')>50</option>
                        <option value="100" @selected($perPageRaw === '100')>100</option>
                        <option value="all" @selected($perPageRaw === 'all')>Всі</option>
                    </select>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="orders-table min-w-full text-sm border border-gray-200">
                            <thead>
                                <tr>
                                    @foreach($orderTableColumns as $column)
                                        @php($label = $orderTableColumnDefinitions[$column])
                                        <th data-order-column="{{ $column }}" class="px-4 py-3 border-b {{ in_array($column, ['amount_due', 'total_cost'], true) ? 'text-right' : 'text-left' }} text-[14px] {{ $column === 'total_cost' ? 'font-bold' : '' }}">
                                            <a class="inline-flex items-center gap-1" href="{{ $sortLink($column) }}">
                                                {{ $label }}
                                                @if ($sort === $column)
                                                    <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                                @else
                                                    <span class="text-gray-400">↕</span>
                                                @endif
                                            </a>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    @php([$paymentStatusLabel, $paymentStatusClass] = $paymentStatus($order))
                                    <tr class="order-row {{ $loop->odd ? 'row-alt' : 'row-base' }}" tabindex="0">
                                        @foreach($orderTableColumns as $column)
                                            @switch($column)
                                                @case('date')
                                                    <td class="px-4 py-3 border-b">{{ $formatOrderDate($order->updated_at) }}</td>
                                                    @break
                                                @case('status')
                                                    <td class="px-4 py-3 border-b">
                                                        <span class="inline-flex whitespace-nowrap rounded-md border px-3 py-1 text-sm font-semibold {{ \App\Models\Order::statusStyle($order->status) }}">{{ $order->statusLabel() }}</span>
                                                    </td>
                                                    @break
                                                @case('number')
                                                    <td class="px-4 py-3 border-b">
                                                        <a href="{{ route('orders.show', $order) }}" class="text-indigo-600 hover:text-indigo-900">{{ $order->order_number }}</a>
                                                    </td>
                                                    @break
                                                @case('payment')
                                                    <td class="px-4 py-3 border-b">
                                                        <span class="inline-flex whitespace-nowrap rounded-md border px-3 py-1 text-sm font-semibold {{ $paymentStatusClass }}">{{ $paymentStatusLabel }}</span>
                                                    </td>
                                                    @break
                                                @case('customer')
                                                    <td class="px-4 py-3 border-b">
                                                        @if(($ordersPermissions['clients'] ?? false) && $order->client)
                                                            <a href="{{ route('orders.clients.show', $order->client) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">{{ $order->client->name ?: ($order->customer_name ?: '—') }}</a>
                                                        @else
                                                            {{ $order->customer_name ?: '—' }}
                                                        @endif
                                                    </td>
                                                    @break
                                                @case('user')
                                                    <td class="px-4 py-3 border-b">{{ $order->lastEditedBy?->name ?? '—' }}</td>
                                                    @break
                                                @case('amount_due')
                                                    <td class="px-4 py-3 border-b text-right">{{ $formatOrderMoney($order->amount_due) }}</td>
                                                    @break
                                                @case('total_cost')
                                                    <td class="px-4 py-3 border-b text-right font-bold">{{ $formatOrderMoney($order->total_cost) }}</td>
                                                    @break
                                            @endswitch
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($orderTableColumns) }}" class="px-4 py-8 text-center text-gray-500">
                                            Замовлення ще не створено.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $orders->links() }}</div>
                </div>
            </div>

            <div x-show="showEditor" x-cloak class="fixed inset-0 z-[14000] !mt-0 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50" @click="closeEditor()"></div>
                <div data-orders-table-display-modal class="relative w-[820px] max-w-full rounded-xl border border-gray-200 bg-gray-50 p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Редагування відображення таблиці</h3>
                            <p class="mt-2 max-w-2xl text-sm text-gray-600">
                                Перенесіть потрібні поля до блоку «Вибрано». Їх порядок зверху вниз визначає послідовність колонок у таблиці.
                            </p>
                        </div>
                        <button type="button" @click="closeEditor()" class="text-2xl leading-none text-gray-400 hover:text-gray-700" aria-label="Закрити">&times;</button>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <section class="rounded-lg border border-gray-300 bg-white p-4">
                            <h4 class="font-semibold text-gray-800">Доступно</h4>
                            <p class="mt-1 text-xs text-gray-500">Поля, які зараз не відображаються.</p>
                            <div class="mt-3 min-h-[260px] space-y-2">
                                <template x-for="column in availableColumns" :key="column">
                                    <button type="button" @click="selectColumn(column)" class="flex w-full items-center justify-between rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-left text-sm text-gray-700 hover:border-indigo-300 hover:bg-indigo-50">
                                        <span x-text="definitions[column]"></span>
                                        <span class="font-bold text-indigo-600" aria-hidden="true">→</span>
                                    </button>
                                </template>
                                <p x-show="availableColumns.length === 0" class="py-8 text-center text-sm text-gray-400">Усі поля вибрано</p>
                            </div>
                        </section>

                        <section class="rounded-lg border border-indigo-200 bg-white p-4">
                            <h4 class="font-semibold text-gray-800">Вибрано</h4>
                            <p class="mt-1 text-xs text-gray-500">Використовуйте стрілки, щоб змінити черговість.</p>
                            <div class="mt-3 min-h-[260px] space-y-2">
                                <template x-for="(column, index) in draftSelected" :key="column">
                                    <div class="flex items-center gap-2 rounded-md border border-indigo-200 bg-indigo-50 px-2 py-2 text-sm text-gray-800">
                                        <button type="button" @click="removeColumn(column)" :disabled="draftSelected.length === 1" class="rounded px-1.5 py-1 font-bold text-indigo-600 hover:bg-white disabled:cursor-not-allowed disabled:text-gray-300" title="Прибрати поле" aria-label="Прибрати поле">←</button>
                                        <span class="min-w-0 flex-1 font-medium" x-text="definitions[column]"></span>
                                        <button type="button" @click="moveColumn(index, -1)" :disabled="index === 0" class="rounded px-1.5 py-1 text-indigo-700 hover:bg-white disabled:cursor-not-allowed disabled:text-gray-300" title="Перемістити вище" aria-label="Перемістити вище">↑</button>
                                        <button type="button" @click="moveColumn(index, 1)" :disabled="index === draftSelected.length - 1" class="rounded px-1.5 py-1 text-indigo-700 hover:bg-white disabled:cursor-not-allowed disabled:text-gray-300" title="Перемістити нижче" aria-label="Перемістити нижче">↓</button>
                                    </div>
                                </template>
                            </div>
                        </section>
                    </div>

                    <p x-show="saveError" x-cloak x-text="saveError" class="mt-4 rounded-md bg-red-100 px-3 py-2 text-sm text-red-700"></p>
                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" @click="closeEditor()" :disabled="isSaving" class="inline-flex h-[40px] items-center rounded-md border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 hover:bg-gray-100 disabled:opacity-50">Скасувати</button>
                        <button type="button" @click="applyColumns()" :disabled="isSaving || draftSelected.length === 0" class="inline-flex h-[40px] items-center rounded-md bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50" x-text="isSaving ? 'Збереження...' : 'Застосувати'"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
        window.ordersTableDisplayEditor = function (config) {
            return {
                definitions: config.definitions || {},
                selected: Array.isArray(config.selected) ? [...config.selected] : [],
                draftSelected: [],
                updateUrl: config.updateUrl || '',
                showEditor: false,
                isSaving: false,
                saveError: '',

                get availableColumns() {
                    return Object.keys(this.definitions).filter((column) => !this.draftSelected.includes(column));
                },

                openEditor() {
                    this.draftSelected = [...this.selected];
                    this.saveError = '';
                    this.showEditor = true;
                    document.documentElement.classList.add('overflow-hidden');
                },

                closeEditor() {
                    if (this.isSaving || !this.showEditor) {
                        return;
                    }

                    this.showEditor = false;
                    this.saveError = '';
                    document.documentElement.classList.remove('overflow-hidden');
                },

                selectColumn(column) {
                    if (this.definitions[column] && !this.draftSelected.includes(column)) {
                        this.draftSelected.push(column);
                    }
                },

                removeColumn(column) {
                    if (this.draftSelected.length <= 1) {
                        return;
                    }

                    this.draftSelected = this.draftSelected.filter((value) => value !== column);
                },

                moveColumn(index, direction) {
                    const target = index + direction;
                    if (target < 0 || target >= this.draftSelected.length) {
                        return;
                    }

                    const columns = [...this.draftSelected];
                    [columns[index], columns[target]] = [columns[target], columns[index]];
                    this.draftSelected = columns;
                },

                async applyColumns() {
                    if (this.isSaving || this.draftSelected.length === 0) {
                        return;
                    }

                    this.isSaving = true;
                    this.saveError = '';
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const response = await fetch(this.updateUrl, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify({ columns: this.draftSelected }),
                        });
                        const payload = await response.json();
                        if (!response.ok || !payload?.ok) {
                            const validationMessage = payload?.errors
                                ? Object.values(payload.errors).flat()[0]
                                : null;
                            throw new Error(validationMessage || payload?.message || 'Не вдалося зберегти налаштування таблиці.');
                        }

                        this.selected = [...this.draftSelected];
                        window.location.reload();
                    } catch (error) {
                        this.saveError = error?.message || 'Не вдалося зберегти налаштування таблиці.';
                        this.isSaving = false;
                    }
                },
            };
        };

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-orders-filters]');
            if (!form) {
                return;
            }

            let applyTimer = null;
            let changedFilter = null;
            let isSubmitting = false;

            const submitFilters = () => {
                if (isSubmitting || !changedFilter) {
                    return;
                }

                isSubmitting = true;
                window.clearTimeout(applyTimer);
                applyTimer = null;
                form.requestSubmit();
            };

            const updateSelectedCount = (details) => {
                const count = details.querySelectorAll('[data-orders-filter-input]:checked').length;
                const label = details.querySelector('[data-orders-filter-count]');
                if (label) {
                    label.textContent = count > 0
                        ? `Обрано: ${count}`
                        : (details.hasAttribute('data-orders-client-filter') ? '' : 'Усі');
                }
            };

            const isFilterOpen = (filter) => filter.tagName === 'DETAILS'
                ? filter.open
                : !filter.querySelector('[data-orders-filter-panel]')?.hidden;

            const openFilter = (filter) => {
                if (filter.tagName === 'DETAILS') {
                    filter.open = true;
                    return;
                }

                const panel = filter.querySelector('[data-orders-filter-panel]');
                if (panel) {
                    panel.hidden = false;
                }
            };

            const closeFilter = (filter) => {
                if (filter.tagName === 'DETAILS') {
                    filter.open = false;
                    return;
                }

                const panel = filter.querySelector('[data-orders-filter-panel]');
                if (panel) {
                    panel.hidden = true;
                }
                if (changedFilter === filter) {
                    submitFilters();
                }
            };

            form.querySelectorAll('[data-orders-filter-input]').forEach((input) => {
                input.addEventListener('change', () => {
                    changedFilter = input.closest('[data-orders-filter-details]');
                    updateSelectedCount(changedFilter);
                    window.clearTimeout(applyTimer);
                    applyTimer = window.setTimeout(submitFilters, 2000);
                });
            });

            form.querySelectorAll('[data-orders-client-search]').forEach((searchInput) => {
                const filter = searchInput.closest('[data-orders-filter-details]');

                searchInput.addEventListener('focus', () => openFilter(filter));
                searchInput.addEventListener('click', () => openFilter(filter));
                searchInput.addEventListener('input', () => {
                    const normalizeSearchText = (value) => String(value || '')
                        .normalize('NFKC')
                        .trim()
                        .toLocaleLowerCase('uk-UA');
                    const query = normalizeSearchText(searchInput.value);
                    openFilter(filter);
                    let visibleOptions = 0;

                    filter.querySelectorAll('[data-orders-client-option]').forEach((option) => {
                        const clientName = normalizeSearchText(option.dataset.clientName);
                        const matches = query === '' || clientName.includes(query);
                        option.hidden = !matches;
                        option.style.setProperty('display', matches ? 'flex' : 'none', 'important');
                        if (matches) {
                            visibleOptions += 1;
                        }
                    });

                    const noResults = filter.querySelector('[data-orders-client-no-results]');
                    if (noResults) {
                        noResults.hidden = visibleOptions > 0;
                        noResults.style.setProperty('display', visibleOptions > 0 ? 'none' : 'block', 'important');
                    }
                });
            });

            form.querySelectorAll('[data-orders-filter-details]').forEach((details) => {
                if (details.tagName !== 'DETAILS') {
                    return;
                }

                details.addEventListener('toggle', () => {
                    if (!details.open && changedFilter === details) {
                        submitFilters();
                    }
                });
            });

            document.addEventListener('click', (event) => {
                form.querySelectorAll('[data-orders-filter-details]').forEach((filter) => {
                    if (isFilterOpen(filter) && !filter.contains(event.target)) {
                        closeFilter(filter);
                    }
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                form.querySelectorAll('[data-orders-filter-details]').forEach((filter) => {
                    if (isFilterOpen(filter)) {
                        closeFilter(filter);
                    }
                });
            });
        });

        window.changeOrdersPerPage = function (value) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        };
    </script>
</x-app-layout>
