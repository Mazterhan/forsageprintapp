<x-app-layout>
    @section('title', __('Картка клієнта. :name', ['name' => $client->name]))

    @php
        $readOnly = $readOnly ?? false;
        $clientOverpaymentTotal = (float) $client->payments
            ->where('payment_type', 'prepayment')
            ->sum('amount_uah') - (float) $client->payments
                ->where('is_from_overpayment', true)
                ->sum('amount_uah');
        $clientOverpaymentTotal = max(0, $clientOverpaymentTotal);
        $sectionFields = [
            'main' => ['name', 'type', 'status', 'category', 'is_vip', 'tags', 'notes', 'manager_id'],
            'contacts' => ['contact_name', 'phones', 'emails', 'messengers', 'source'],
            'delivery' => ['delivery_address', 'delivery_notes', 'delivery_addresses'],
        ];
        $allowedSections = ['main', 'orders', 'payments', 'contacts', 'delivery', 'service'];
        $initialSection = in_array($requestedSection ?? null, $allowedSections, true)
            ? $requestedSection
            : 'main';
        foreach ($sectionFields as $section => $fields) {
            if ($errors->hasAny($fields)) {
                $initialSection = $section;
                break;
            }
        }
        $menuItems = [
            ['key' => 'main', 'label' => 'Основні дані'],
            ['key' => 'orders', 'label' => 'Замовлення'],
            ['key' => 'payments', 'label' => 'Платежі'],
            ['key' => 'contacts', 'label' => 'Контакти'],
            ['key' => 'delivery', 'label' => 'Доставка'],
            ['key' => 'service', 'label' => 'Службові поля'],
        ];
        $formatOrderMoney = static function ($value): string {
            $formatted = number_format((float) $value, 2, '.', ' ');
            return preg_replace('/\.0+$/', '', $formatted) ?? $formatted;
        };
        $formatOrderDate = static fn ($date): string => $date
            ? $date->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i')
            : '';
        $orderNextDirection = fn (string $column): string => ($orderSort === $column && $orderDirection === 'asc') ? 'desc' : 'asc';
        $clientCardRoute = $readOnly ? 'orders.clients.show' : 'orders.clients.edit';
        $orderSortLink = fn (string $column): string => route($clientCardRoute, array_merge(
            ['client' => $client],
            request()->except(['orders_page', 'order_sort', 'order_direction']),
            ['section' => 'orders', 'order_sort' => $column, 'order_direction' => $orderNextDirection($column)]
        ));
        $orderPaymentStatus = static function ($order): array {
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
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Картка клієнта. :name', ['name' => $client->name]) }}
                </h2>
                @if($clientOverpaymentTotal > 0)
                    <div data-client-overpayment-total class="mt-1 text-sm font-semibold text-blue-700">
                        Переплата: {{ $formatOrderMoney($clientOverpaymentTotal) }}
                    </div>
                @endif
            </div>
            <div class="flex flex-wrap items-center justify-end gap-3">
                @if($readOnly)
                    <a
                        href="{{ route('orders.clients.edit', $client) }}"
                        class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                    >
                        Редагувати
                    </a>
                @endif
                <form method="POST" action="{{ route('orders.clients.deactivate', $client) }}">
                    @csrf
                    @method('PATCH')
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                        onclick="return confirm('Деактивувати цього замовника?')"
                    >
                        Деактивувати
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <style>
        .client-orders-table thead tr {
            background-color: #FCEEDF;
        }

        .client-order-row {
            transition: background-color 0.5s ease, background-image 0.5s ease;
        }

        .client-order-row td {
            background: transparent;
        }

        .client-order-row.row-alt {
            background-color: #F9FAFB;
        }

        .client-order-row.row-base {
            background-color: #FFFFFF;
        }

        .client-order-row:hover {
            background-color: #D8F1F2;
            background-image: linear-gradient(90deg, #e9f7f7 0%, #D8F1F2 100%);
        }
    </style>

    <div class="py-12">
        <div
            x-data="clientCard({
                initialSection: @js($initialSection),
                paymentStoreUrl: @js(route('orders.clients.payments.store', $client)),
                paymentOrdersUrl: @js(route('orders.clients.payments.orders', $client)),
                ratesUrl: @js(route('orders.payments.exchange-rates')),
                today: @js(now('Europe/Kiev')->format('Y-m-d')),
                currentTime: @js(now('Europe/Kiev')->format('H:i')),
                overpaymentTotal: @js((int) $clientOverpaymentTotal),
                payments: @js($paymentModalData),
            })"
            @keydown.escape.window="closePaymentModal()"
            class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12 space-y-6"
        >
            @if (session('status'))
                <div class="text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="text-sm text-red-700 bg-red-100 px-4 py-2 rounded">
                    {{ __('Будь ласка, виправте помилки нижче.') }}
                </div>
            @endif

            <div
                class="grid items-start gap-6"
                style="grid-template-columns: 240px minmax(0, 1fr);"
            >
                <aside class="sticky top-6 rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
                    <nav class="flex flex-col gap-2" aria-label="Розділи картки замовника">
                        @foreach($menuItems as $menuItem)
                            <button
                                type="button"
                                @click="activeSection = '{{ $menuItem['key'] }}'"
                                :class="activeSection === '{{ $menuItem['key'] }}'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'bg-white text-gray-700 hover:bg-gray-100'"
                                class="w-full rounded-md px-4 py-3 text-left text-sm font-semibold transition"
                            >
                                {{ $menuItem['label'] }}
                            </button>
                        @endforeach
                    </nav>
                </aside>

                <div class="min-w-0">
                    <form method="POST" action="{{ route('orders.clients.update', $client) }}">
                        @csrf
                        @method('PATCH')

                        <div>
                            <fieldset @disabled($readOnly)>
                                @include('clients.partials.form', ['client' => $client, 'sectioned' => true])
                            </fieldset>

                            <div
                                x-show="activeSection === 'payments'"
                                x-cloak
                                class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
                            >
                                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <h3 class="text-sm font-semibold uppercase text-gray-700">Платежі</h3>
                                        <button
                                            type="button"
                                            @click="showPaymentCodes = !showPaymentCodes"
                                            :aria-pressed="showPaymentCodes"
                                            :class="showPaymentCodes
                                                ? 'border-indigo-300 bg-indigo-50 text-indigo-700'
                                                : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                                            class="inline-flex items-center rounded-md border px-4 py-2 text-sm font-semibold shadow-sm transition"
                                        >
                                            Відобразити код платежу
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($clientOverpaymentTotal > 0)
                                            <button
                                                type="button"
                                                @click="openOverpaymentPayment()"
                                                class="inline-flex items-center rounded-md border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm hover:bg-blue-100"
                                            >
                                                Списати з переплати
                                            </button>
                                        @endif
                                        <button
                                            type="button"
                                            @click="openCreatePayment()"
                                            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                                        >
                                            Внести платіж
                                        </button>
                                    </div>
                                </div>

                                <div class="overflow-x-auto rounded-lg border border-gray-200">
                                    <table class="min-w-full text-sm">
                                        <thead style="background-color: #D3D4D4;">
                                            <tr>
                                                <th x-show="showPaymentCodes" x-cloak class="w-[310px] px-3 py-2 text-left">Код платежу</th>
                                                <th class="px-3 py-2 text-left">Дата / час</th>
                                                <th class="px-3 py-2 text-left">Замовлення / тип</th>
                                                <th class="px-3 py-2 text-right">Сума</th>
                                                <th class="px-3 py-2 text-left">Валюта</th>
                                                <th class="px-3 py-2 text-left">Користувач</th>
                                                <th class="w-[130px] px-3 py-2 text-center">Дії</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($client->payments as $payment)
                                                <tr class="border-t border-gray-200 {{ $payment->is_edited ? 'bg-yellow-100' : 'bg-white' }}">
                                                    <td x-show="showPaymentCodes" x-cloak class="px-3 py-2 font-mono text-xs text-gray-700">
                                                        {{ $payment->public_id }}
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        {{ $payment->paid_at->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i') }}
                                                    </td>
                                                    <td class="px-3 py-2 font-medium">
                                                        <div>
                                                            @if($payment->payment_type === 'order')
                                                                {{ $payment->order?->order_number ?? '—' }}
                                                            @elseif($payment->payment_type === 'writeoff')
                                                                Просте списання
                                                            @else
                                                                Переплата
                                                            @endif
                                                        </div>
                                                        @if($payment->is_from_overpayment)
                                                            <div class="mt-0.5 text-xs font-semibold text-blue-600">з переплати</div>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-right font-semibold">
                                                        <div>{{ number_format((int) $payment->amount_uah, 0, '.', ' ') }} грн</div>
                                                        @if($payment->currency !== 'UAH')
                                                            <div class="mt-0.5 text-xs font-semibold text-blue-600">{{ number_format((int) $payment->amount, 0, '.', ' ') }} {{ $payment->currency === 'USD' ? '$' : '€' }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        грн
                                                    </td>
                                                    <td class="px-3 py-2">{{ $payment->createdBy?->name ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-center">
                                                        <button
                                                            type="button"
                                                            @click="openEditPayment(@js($payment->public_id))"
                                                            class="font-semibold text-indigo-600 hover:text-indigo-900"
                                                        >
                                                            Редагувати
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td :colspan="showPaymentCodes ? 7 : 6" class="px-3 py-8 text-center text-gray-500">
                                                        Платежі ще не додано.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div
                                x-show="activeSection === 'orders'"
                                x-cloak
                                class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
                            >
                                <h3 class="mb-4 text-sm font-semibold uppercase text-gray-700">Замовлення</h3>
                                <div class="overflow-x-auto">
                                    <table class="client-orders-table min-w-full border border-gray-200 text-sm">
                                        <thead>
                                            <tr>
                                                @foreach([
                                                    'date' => 'Дата',
                                                    'number' => 'Номер замовлення',
                                                    'payment' => 'Оплата',
                                                    'customer' => "Ім'я замовника",
                                                    'user' => 'Користувач',
                                                    'amount_due' => 'До сплати',
                                                    'total_cost' => 'Вартість',
                                                ] as $column => $label)
                                                    <th class="border-b px-4 py-3 {{ in_array($column, ['amount_due', 'total_cost'], true) ? 'text-right' : 'text-left' }} text-[14px] {{ $column === 'total_cost' ? 'font-bold' : '' }}">
                                                        <a class="inline-flex items-center gap-1" href="{{ $orderSortLink($column) }}">
                                                            {{ $label }}
                                                            @if($orderSort === $column)
                                                                <span class="text-gray-600">{{ $orderDirection === 'asc' ? '▲' : '▼' }}</span>
                                                            @else
                                                                <span class="text-gray-400">↕</span>
                                                            @endif
                                                        </a>
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($clientOrders as $clientOrder)
                                                @php([$orderPaymentStatusLabel, $orderPaymentStatusClass] = $orderPaymentStatus($clientOrder))
                                                <tr class="client-order-row {{ $loop->odd ? 'row-alt' : 'row-base' }}" tabindex="0">
                                                    <td class="border-b px-4 py-3">{{ $formatOrderDate($clientOrder->updated_at) }}</td>
                                                    <td class="border-b px-4 py-3">
                                                        <a href="{{ route('orders.show', $clientOrder) }}" class="text-indigo-600 hover:text-indigo-900">
                                                            {{ $clientOrder->order_number }}
                                                        </a>
                                                    </td>
                                                    <td class="border-b px-4 py-3">
                                                        <span class="inline-flex whitespace-nowrap rounded-md border px-3 py-1 text-sm font-semibold {{ $orderPaymentStatusClass }}">
                                                            {{ $orderPaymentStatusLabel }}
                                                        </span>
                                                    </td>
                                                    <td class="border-b px-4 py-3">{{ $clientOrder->customer_name ?: '—' }}</td>
                                                    <td class="border-b px-4 py-3">{{ $clientOrder->lastEditedBy?->name ?? '—' }}</td>
                                                    <td class="border-b px-4 py-3 text-right">{{ $formatOrderMoney($clientOrder->amount_due) }}</td>
                                                    <td class="border-b px-4 py-3 text-right font-bold">{{ $formatOrderMoney($clientOrder->total_cost) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                                        Замовлення ще не створено.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4">{{ $clientOrders->links() }}</div>
                            </div>

                            @unless($readOnly)
                                <div
                                    x-show="['main', 'contacts', 'delivery'].includes(activeSection)"
                                    x-cloak
                                    class="mt-6 flex justify-end"
                                >
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Зберегти зміни
                                    </button>
                                </div>
                            @endunless
                        </div>
                    </form>
                </div>
            </div>

            <div
                x-show="showPaymentModal"
                x-cloak
                class="fixed inset-0 z-[14000] !mt-0 flex items-center justify-center p-4"
            >
                <div class="absolute inset-0 bg-black/50" @click="closePaymentModal()"></div>
                <div class="relative max-h-[92vh] w-[1100px] max-w-full overflow-y-auto rounded-xl border border-gray-200 bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3
                                class="text-lg font-semibold text-gray-900"
                                x-text="isEditingPayment ? 'Редагування платежу' : (paymentForm.fromOverpayment ? 'Платіж з переплати' : 'Внесення платежу')"
                            ></h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $client->name }}</p>
                        </div>
                        <button type="button" @click="closePaymentModal()" class="text-2xl leading-none text-gray-400 hover:text-gray-700" aria-label="Закрити">&times;</button>
                    </div>

                    <div x-show="paymentError" x-text="paymentError" class="mt-4 rounded-md bg-red-100 px-4 py-3 text-sm text-red-700"></div>

                    <div data-payment-form-panel class="mt-5 rounded-lg border border-gray-200 bg-gray-50 p-5">
                        <h4 class="font-semibold text-gray-800">Дані платежу</h4>

                    <div class="mt-4 grid grid-cols-1 gap-4" :class="isForeignPaymentCurrency() ? 'md:grid-cols-5' : 'md:grid-cols-4'">
                        <div>
                            <label for="client-payment-amount" class="block text-sm font-medium text-gray-700">Сума операції</label>
                            <input id="client-payment-amount" x-model="paymentForm.amount" @input="recalculatePaymentAmountUah()" type="text" inputmode="numeric" autocomplete="off" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0">
                        </div>
                        <div>
                            <label for="client-payment-currency" class="block text-sm font-medium text-gray-700">Валюта операції</label>
                            <select id="client-payment-currency" x-model="paymentForm.currency" @change="paymentCurrencyChanged()" :disabled="paymentForm.fromOverpayment" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100">
                                <option value="UAH">UAH</option>
                                <option value="USD" :disabled="!paymentRates.USD" x-text="paymentRateOption('USD')"></option>
                                <option value="EUR" :disabled="!paymentRates.EUR" x-text="paymentRateOption('EUR')"></option>
                            </select>
                            <p x-show="paymentRatesLoading" x-cloak class="mt-1 text-xs text-gray-500">Завантаження курсу…</p>
                            <p x-show="paymentRatesError" x-text="paymentRatesError" x-cloak class="mt-1 text-xs text-red-600"></p>
                        </div>
                        <div x-show="isForeignPaymentCurrency()" x-cloak>
                            <label for="client-payment-amount-uah" class="block text-sm font-medium text-gray-700">Сума списання (ГРН)</label>
                            <input id="client-payment-amount-uah" x-model="paymentForm.amountUah" type="text" inputmode="numeric" autocomplete="off" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0">
                            <p class="mt-1 text-xs text-gray-500" x-text="paymentForm.exchangeRate ? `SALE: ${formatPaymentRate(paymentForm.exchangeRate)} грн/${paymentForm.currency}` : ''"></p>
                        </div>
                        <div>
                            <label for="client-payment-date" class="block text-sm font-medium text-gray-700">Дата</label>
                            <input id="client-payment-date" x-model="paymentForm.date" type="date" :max="today" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="client-payment-time" class="block text-sm font-medium text-gray-700">Час</label>
                            <input id="client-payment-time" x-model="paymentForm.time" type="time" step="60" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div x-show="!paymentForm.fromOverpayment" x-cloak class="mt-5 grid grid-cols-1 items-end gap-4 md:grid-cols-2">
                        <div class="grid grid-cols-2 rounded-lg bg-gray-100 p-1">
                            <button
                                type="button"
                                @click="setPaymentType('order')"
                                :class="paymentForm.paymentType === 'order' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-600'"
                                class="rounded-md px-4 py-2 text-sm font-semibold transition"
                            >
                                Оплата замовлення
                            </button>
                            <button
                                type="button"
                                @click="setPaymentType('prepayment')"
                                :class="paymentForm.paymentType === 'prepayment' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-600'"
                                class="rounded-md px-4 py-2 text-sm font-semibold transition"
                            >
                                Внесення переплати
                            </button>
                        </div>

                        <div x-show="paymentForm.paymentType === 'order'" x-cloak>
                            <label for="client-payment-order" class="block text-sm font-medium text-gray-700">Номер замовлення <span class="text-red-600">*</span></label>
                            <select
                                id="client-payment-order"
                                x-model="paymentForm.orderPublicId"
                                :required="!paymentForm.fromOverpayment && paymentForm.paymentType === 'order'"
                                @focus="loadPaymentOrders()"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="" x-text="isLoadingPaymentOrders ? 'Завантаження...' : 'Оберіть замовлення'"></option>
                                <template x-for="order in availablePaymentOrders" :key="order.id">
                                    <option :value="order.id" :disabled="Boolean(order.unavailable)" x-text="paymentOrderOption(order)"></option>
                                </template>
                            </select>
                            <p x-show="!isLoadingPaymentOrders && paymentOrdersLoaded && availablePaymentOrders.length === 0" x-cloak class="mt-1 text-xs text-amber-700">У цього клієнта ще немає прив'язаних замовлень.</p>
                        </div>
                    </div>

                    <div x-show="paymentForm.fromOverpayment" x-cloak class="mt-5 grid grid-cols-1 items-end gap-4 md:grid-cols-2">
                        <div class="grid grid-cols-2 rounded-lg bg-gray-100 p-1">
                            <button
                                type="button"
                                @click="setPaymentType('order')"
                                :class="paymentForm.paymentType === 'order' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-600'"
                                class="rounded-md px-4 py-2 text-sm font-semibold transition"
                            >
                                Платіж за замовлення
                            </button>
                            <button
                                type="button"
                                @click="setPaymentType('writeoff')"
                                :class="paymentForm.paymentType === 'writeoff' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-600'"
                                class="rounded-md px-4 py-2 text-sm font-semibold transition"
                            >
                                Просте списання
                            </button>
                        </div>

                        <div x-show="paymentForm.paymentType === 'order'" x-cloak>
                            <label for="client-overpayment-order" class="block text-sm font-medium text-gray-700">Номер замовлення <span class="text-red-600">*</span></label>
                            <select
                                id="client-overpayment-order"
                                x-model="paymentForm.orderPublicId"
                                :required="paymentForm.paymentType === 'order'"
                                @focus="loadPaymentOrders()"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="" x-text="isLoadingPaymentOrders ? 'Завантаження...' : 'Оберіть замовлення'"></option>
                                <template x-for="order in availablePaymentOrders" :key="order.id">
                                    <option :value="order.id" :disabled="Boolean(order.unavailable)" x-text="paymentOrderOption(order)"></option>
                                </template>
                            </select>
                            <p x-show="!isLoadingPaymentOrders && paymentOrdersLoaded && availablePaymentOrders.length === 0" x-cloak class="mt-1 text-xs text-amber-700">У цього клієнта ще немає прив'язаних замовлень.</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="client-payment-comment" class="block text-sm font-medium text-gray-700">
                            Коментар <span x-show="paymentForm.paymentType === 'writeoff'" x-cloak class="text-red-600">*</span>
                        </label>
                        <textarea id="client-payment-comment" x-model="paymentForm.comment" :required="paymentForm.paymentType === 'writeoff'" rows="3" maxlength="2000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Додаткові відомості про платіж"></textarea>
                        <p x-show="paymentForm.paymentType === 'writeoff'" x-cloak class="mt-1 text-xs" :class="countPaymentCommentCharacters() >= 20 ? 'text-green-700' : 'text-amber-700'">
                            Щонайменше 20 букв або цифр: <span x-text="countPaymentCommentCharacters()"></span>/20
                        </p>
                    </div>

                        <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4">
                            <button type="button" @click="closePaymentModal()" :disabled="isSavingPayment" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">Скасувати</button>
                            <button type="button" @click="submitPayment()" :disabled="isSavingPayment" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-text="isSavingPayment ? 'Збереження...' : (isEditingPayment ? 'Зберегти зміни' : 'Додати платіж')"></span>
                            </button>
                        </div>
                    </div>

                    <div x-show="isEditingPayment && activePaymentHistories.length > 0" x-cloak class="mt-6 border-t border-gray-200 pt-5">
                        <h4 class="text-sm font-semibold uppercase text-gray-700">Історія змін платежу</h4>
                        <div class="mt-3 space-y-3">
                            <template x-for="(history, historyIndex) in activePaymentHistories" :key="`history-${historyIndex}`">
                                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm">
                                    <div class="flex flex-wrap justify-between gap-2 font-semibold text-gray-800">
                                        <span x-text="history.user"></span>
                                        <span x-text="history.date"></span>
                                    </div>
                                    <div class="mt-2 space-y-2">
                                        <template x-for="(change, changeIndex) in history.changes" :key="`change-${historyIndex}-${changeIndex}`">
                                            <div class="grid gap-1 sm:grid-cols-[150px_1fr]">
                                                <span class="font-medium text-gray-700" x-text="change.label"></span>
                                                <span class="break-words text-gray-700">
                                                    <span class="text-red-700" x-text="change.before ?? '—'"></span>
                                                    <span class="px-1 text-gray-400">→</span>
                                                    <span class="text-green-700" x-text="change.after ?? '—'"></span>
                                                </span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        function clientCard(config) {
            const emptyPayment = () => ({
                amount: '',
                amountUah: '',
                calculatedAmountUah: '',
                currency: 'UAH',
                exchangeRate: null,
                date: config.today || '',
                time: config.currentTime || '',
                paymentType: 'order',
                fromOverpayment: false,
                orderPublicId: '',
                orderNumber: '',
                orderAmountDue: null,
                comment: '',
                updateUrl: '',
            });

            return {
                activeSection: config.initialSection || 'main',
                paymentStoreUrl: config.paymentStoreUrl || '',
                paymentOrdersUrl: config.paymentOrdersUrl || '',
                paymentRatesUrl: config.ratesUrl || '',
                payments: Array.isArray(config.payments) ? config.payments : [],
                overpaymentTotal: Number(config.overpaymentTotal) || 0,
                today: config.today || '',
                showPaymentCodes: false,
                availablePaymentOrders: [],
                paymentOrdersLoaded: false,
                isLoadingPaymentOrders: false,
                showPaymentModal: false,
                isEditingPayment: false,
                isSavingPayment: false,
                paymentError: '',
                paymentRates: {},
                paymentRatesFetchedAt: '',
                paymentRatesLoading: false,
                paymentRatesError: '',
                paymentForm: emptyPayment(),
                activePaymentHistories: [],

                openCreatePayment() {
                    this.isEditingPayment = false;
                    this.paymentError = '';
                    this.paymentForm = emptyPayment();
                    this.activePaymentHistories = [];
                    this.showPaymentModal = true;
                    this.loadPaymentOrders();
                    this.loadPaymentRates();
                },

                openOverpaymentPayment() {
                    this.isEditingPayment = false;
                    this.paymentError = '';
                    this.paymentForm = {
                        ...emptyPayment(),
                        paymentType: 'order',
                        fromOverpayment: true,
                    };
                    this.activePaymentHistories = [];
                    this.showPaymentModal = true;
                    this.loadPaymentOrders();
                },

                openEditPayment(paymentId) {
                    const payment = this.payments.find((item) => item.id === paymentId);
                    if (!payment) {
                        return;
                    }

                    this.isEditingPayment = true;
                    this.paymentError = '';
                    this.paymentForm = {
                        amount: String(payment.amount || ''),
                        amountUah: String(payment.amountUah || payment.amount || ''),
                        calculatedAmountUah: String(payment.calculatedAmountUah || ''),
                        currency: payment.currency || 'UAH',
                        exchangeRate: payment.exchangeRate ? Number(payment.exchangeRate) : null,
                        date: payment.date || this.today,
                        time: payment.time || '',
                        paymentType: payment.paymentType || 'prepayment',
                        fromOverpayment: Boolean(payment.fromOverpayment),
                        orderPublicId: payment.orderPublicId || '',
                        orderNumber: payment.orderNumber || '',
                        orderAmountDue: payment.orderAmountDue ?? null,
                        comment: payment.comment || '',
                        updateUrl: payment.updateUrl || '',
                    };
                    this.activePaymentHistories = Array.isArray(payment.histories) ? payment.histories : [];
                    this.showPaymentModal = true;
                    this.loadPaymentRates();
                    if (this.paymentForm.paymentType === 'order') {
                        this.loadPaymentOrders();
                    }
                },

                closePaymentModal() {
                    if (!this.isSavingPayment) {
                        this.showPaymentModal = false;
                        this.paymentError = '';
                    }
                },

                setPaymentType(type) {
                    this.paymentForm.paymentType = type;
                    if (type !== 'order') {
                        this.paymentForm.orderPublicId = '';
                    } else {
                        this.loadPaymentOrders();
                    }
                },

                countPaymentCommentCharacters() {
                    return (String(this.paymentForm.comment || '').match(/[\p{L}\p{N}]/gu) || []).length;
                },

                paymentOrderOption(order) {
                    const amountDue = new Intl.NumberFormat('uk-UA', {
                        maximumFractionDigits: 0,
                    }).format(Number(order?.amountDue) || 0);

                    return `${order?.number || '—'} — Сума до сплати: ${amountDue} ГРН`;
                },

                async loadPaymentOrders() {
                    if (this.isLoadingPaymentOrders || !this.paymentOrdersUrl) {
                        return;
                    }

                    this.isLoadingPaymentOrders = true;
                    try {
                        const response = await fetch(this.paymentOrdersUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                            },
                            cache: 'no-store',
                        });
                        const payload = await response.json();
                        if (!response.ok || !payload?.ok) {
                            throw new Error(payload?.message || 'Не вдалося завантажити замовлення.');
                        }

                        this.availablePaymentOrders = Array.isArray(payload.orders) ? payload.orders : [];
                        if (this.isEditingPayment && this.paymentForm.orderPublicId && !this.availablePaymentOrders.some((order) => order.id === this.paymentForm.orderPublicId)) {
                            this.availablePaymentOrders.push({
                                id: this.paymentForm.orderPublicId,
                                number: this.paymentForm.orderNumber || 'Поточне замовлення',
                                amountDue: this.paymentForm.orderAmountDue ?? 0,
                                unavailable: true,
                            });
                        }
                        this.paymentOrdersLoaded = true;
                    } catch (error) {
                        this.paymentError = error?.message || 'Не вдалося завантажити замовлення.';
                    } finally {
                        this.isLoadingPaymentOrders = false;
                    }
                },

                validatePayment() {
                    const amount = String(this.paymentForm.amount || '').trim();
                    if (!/^\d+$/.test(amount) || Number.parseInt(amount, 10) <= 0) {
                        return 'Сума повинна бути цілим числом більше нуля.';
                    }
                    if (!['UAH', 'USD', 'EUR'].includes(this.paymentForm.currency)) {
                        return 'Оберіть доступну валюту.';
                    }
                    if (this.paymentForm.fromOverpayment && this.paymentForm.currency !== 'UAH') {
                        return 'Списання з переплати доступне лише у гривні.';
                    }
                    if (this.isForeignPaymentCurrency()) {
                        const amountUah = String(this.paymentForm.amountUah || '').trim();
                        if (!this.paymentForm.exchangeRate || !/^\d+$/.test(amountUah) || Number.parseInt(amountUah, 10) <= 0) {
                            return 'Вкажіть суму списання у гривні цілим числом більше нуля.';
                        }
                    }
                    if (!this.paymentForm.date || this.paymentForm.date > this.today) {
                        return 'Оберіть поточну або минулу дату.';
                    }
                    if (!/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(this.paymentForm.time || '')) {
                        return 'Вкажіть коректний час у форматі гг:хх.';
                    }
                    if (this.paymentForm.paymentType === 'order' && !this.paymentForm.orderPublicId) {
                        return 'Оберіть номер замовлення.';
                    }
                    if (this.paymentForm.paymentType === 'writeoff' && this.countPaymentCommentCharacters() < 20) {
                        return 'Для простого списання коментар має містити щонайменше 20 букв або цифр.';
                    }
                    if (this.paymentForm.fromOverpayment && !this.isEditingPayment && Number.parseInt(amount, 10) > this.overpaymentTotal) {
                        return 'Сума списання перевищує доступну переплату клієнта.';
                    }

                    return '';
                },

                async submitPayment() {
                    if (this.isSavingPayment) {
                        return;
                    }

                    this.paymentError = this.validatePayment();
                    if (this.paymentError) {
                        return;
                    }

                    const amountUahLabel = this.paymentForm.paymentType === 'prepayment'
                        ? 'Сума поповнення переплати'
                        : 'Сума списання';
                    const conversionDetails = this.isForeignPaymentCurrency()
                        ? `\nКурс SALE ПриватБанку: ${this.formatPaymentRate(this.paymentForm.exchangeRate)} грн/${this.paymentForm.currency}.\n${amountUahLabel}: ${this.paymentForm.amountUah} грн.`
                        : '';
                    const overpaymentWarning = this.paymentForm.fromOverpayment
                        ? '\nСума платежу буде списана з переплати клієнта.'
                        : '';
                    const confirmation = (this.isEditingPayment
                        ? 'Підтверджуєте, що всі зміни платежу внесено правильно?'
                        : 'Підтверджуєте, що всі дані платежу внесено правильно?') + conversionDetails + overpaymentWarning;
                    if (!window.confirm(confirmation)) {
                        return;
                    }

                    this.isSavingPayment = true;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const response = await fetch(this.isEditingPayment ? this.paymentForm.updateUrl : this.paymentStoreUrl, {
                            method: this.isEditingPayment ? 'PATCH' : 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify({
                                amount: Number.parseInt(this.paymentForm.amount, 10),
                                amount_uah: this.isForeignPaymentCurrency() ? Number.parseInt(this.paymentForm.amountUah, 10) : Number.parseInt(this.paymentForm.amount, 10),
                                currency: this.paymentForm.currency,
                                payment_date: this.paymentForm.date,
                                payment_time: this.paymentForm.time,
                                payment_type: this.paymentForm.paymentType,
                                payment_source: this.paymentForm.fromOverpayment ? 'overpayment' : 'direct',
                                order_public_id: this.paymentForm.paymentType === 'order' ? this.paymentForm.orderPublicId : null,
                                comment: String(this.paymentForm.comment || '').trim() || null,
                            }),
                        });
                        const payload = await response.json();

                        if (!response.ok || !payload?.ok) {
                            const validationMessages = payload?.errors
                                ? Object.values(payload.errors).flat().join(' ')
                                : '';
                            throw new Error(validationMessages || payload?.message || 'Не вдалося зберегти платіж.');
                        }

                        if (payload.notification) {
                            window.alert(payload.notification);
                        }
                        window.location.href = payload.redirect_url;
                    } catch (error) {
                        this.paymentError = error?.message || 'Не вдалося зберегти платіж.';
                    } finally {
                        this.isSavingPayment = false;
                    }
                },

                isForeignPaymentCurrency() {
                    return ['USD', 'EUR'].includes(this.paymentForm.currency);
                },

                formatPaymentRate(rate) {
                    return Number(rate || 0).toLocaleString('uk-UA', { minimumFractionDigits: 2, maximumFractionDigits: 6 });
                },

                paymentRateOption(currency) {
                    return this.paymentRates[currency]
                        ? `${currency} — ${this.formatPaymentRate(this.paymentRates[currency])} грн`
                        : `${currency} — курс недоступний`;
                },

                paymentCurrencyChanged() {
                    if (!this.isForeignPaymentCurrency()) {
                        this.paymentForm.amountUah = this.paymentForm.amount;
                        this.paymentForm.calculatedAmountUah = this.paymentForm.amount;
                        this.paymentForm.exchangeRate = null;
                        return;
                    }

                    this.paymentForm.exchangeRate = Number(this.paymentRates[this.paymentForm.currency] || 0) || null;
                    this.recalculatePaymentAmountUah();
                },

                recalculatePaymentAmountUah() {
                    if (!this.isForeignPaymentCurrency()) {
                        this.paymentForm.amountUah = this.paymentForm.amount;
                        return;
                    }

                    const amount = Number.parseInt(String(this.paymentForm.amount || ''), 10);
                    const rate = Number(this.paymentRates[this.paymentForm.currency] || this.paymentForm.exchangeRate || 0);
                    if (!Number.isInteger(amount) || amount <= 0 || rate <= 0) {
                        this.paymentForm.amountUah = '';
                        this.paymentForm.calculatedAmountUah = '';
                        return;
                    }

                    const calculated = Math.ceil(amount * rate);
                    this.paymentForm.exchangeRate = rate;
                    this.paymentForm.calculatedAmountUah = String(calculated);
                    this.paymentForm.amountUah = String(calculated);
                },

                async loadPaymentRates() {
                    if (this.paymentRatesLoading || !this.paymentRatesUrl) {
                        return;
                    }

                    this.paymentRatesLoading = true;
                    this.paymentRatesError = '';
                    try {
                        const response = await fetch(this.paymentRatesUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                        const payload = await response.json();
                        if (!response.ok || !payload?.ok) {
                            throw new Error(payload?.message || 'Не вдалося завантажити курс валют.');
                        }
                        this.paymentRates = payload.rates || {};
                        this.paymentRatesFetchedAt = payload.fetched_at || '';
                    } catch (error) {
                        this.paymentRatesError = error?.message || 'Не вдалося завантажити курс валют.';
                    } finally {
                        this.paymentRatesLoading = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
