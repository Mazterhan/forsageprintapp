<x-app-layout>
    @section('title', __('Замовлення :number', ['number' => $order->order_number]))

    @php
        $items = is_array($order->items) ? $order->items : [];
        $formatOrderMoney = static function ($value): string {
            $formatted = number_format((float) $value, 2, '.', ' ');
            return preg_replace('/\.0+$/', '', $formatted) ?? $formatted;
        };
        $formatOrderDate = static fn ($date): string => $date
            ? $date->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i')
            : '—';
        $orderPermissions = $orderPermissions ?? [];
        $canUpdateOrder = (bool) ($orderPermissions['update'] ?? false);
        $canManageOrderPayments = (bool) ($orderPermissions['payments'] ?? false);
        $orderPaymentsTotal = (float) ($orderPaymentsTotal ?? 0);
        $orderTotalCost = (float) $order->total_cost;
        $orderAmountDue = $orderTotalCost - $orderPaymentsTotal;
        if ($orderPaymentsTotal <= 0) {
            $paymentStatusLabel = 'Не сплачено';
            $paymentStatusClass = 'border-red-300 bg-rose-100 text-red-800';
            $amountDueTextClass = 'text-red-700';
        } elseif ($orderPaymentsTotal < $orderTotalCost) {
            $paymentStatusLabel = 'Частково сплачено';
            $paymentStatusClass = 'border-orange-500 bg-yellow-100 text-orange-800';
            $amountDueTextClass = 'text-amber-600';
        } elseif (abs($orderPaymentsTotal - $orderTotalCost) < 0.005) {
            $paymentStatusLabel = 'Сплачено';
            $paymentStatusClass = 'border-green-300 bg-green-100 text-green-800';
            $amountDueTextClass = 'text-gray-900';
        } else {
            $paymentStatusLabel = 'Є переплата';
            $paymentStatusClass = 'border-blue-400 bg-teal-100 text-blue-800';
            $amountDueTextClass = 'text-blue-700';
        }
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <span class="absolute right-full top-1/2 mr-3 inline-flex -translate-y-1/2 whitespace-nowrap rounded-md border px-3 py-1 text-sm font-semibold {{ $paymentStatusClass }}">
                            {{ $paymentStatusLabel }}
                        </span>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ __('Замовлення :number', ['number' => $order->order_number]) }}
                        </h2>
                    </div>
                    @if($canUpdateOrder)
                        <a href="{{ route('orders.edit', $order) }}" class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Редагувати
                        </a>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    title="Вивантажити замовлення у PDF"
                    aria-label="Вивантажити замовлення у PDF"
                    class="inline-flex h-[38px] w-[42px] items-center justify-center rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M8 15h8M8 18h5" />
                    </svg>
                </button>
                @if($canManageOrderPayments)
                    <button
                        x-data
                        type="button"
                        @click="$dispatch('open-order-payments')"
                        @disabled(! $order->client_id)
                        title="{{ $order->client_id ? 'Відкрити платежі замовлення' : 'Для замовлення не вказано клієнта' }}"
                        class="inline-flex h-[38px] items-center rounded-md border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Платежі
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div
            x-data="orderPaymentPopup({
                storeUrl: @js($canManageOrderPayments && $order->client ? route('orders.clients.payments.store', $order->client) : ''),
                ratesUrl: @js($canManageOrderPayments ? route('orders.payments.exchange-rates') : ''),
                historyUrl: @js($canUpdateOrder ? route('orders.history', $order) : ''),
                orderPublicId: @js($order->public_id),
                orderNumber: @js($order->order_number),
                today: @js(now('Europe/Kiev')->format('Y-m-d')),
                currentTime: @js(now('Europe/Kiev')->format('H:i')),
                overpaymentTotal: @js($clientOverpaymentTotal),
                canAddPayment: @js($canAddOrderPayment),
                payments: @js($paymentModalData),
                openOnLoad: @js(request()->boolean('payments')),
            })"
            @open-order-payments.window="openModal()"
            @keydown.escape.window="closeModal()"
            class="max-w-[1700px] mx-auto space-y-4 px-6 sm:px-8 lg:px-12"
        >
            <div class="bg-white shadow-sm sm:rounded-lg p-4 text-sm text-gray-800">
                <div class="flex flex-row items-start justify-between gap-6 w-full">
                    <div class="flex-1 min-w-0">
                        <span class="font-semibold">Замовник:</span>
                        {{ $order->client?->name ?: ($order->customer_name ?: '—') }}
                    </div>
                    <div class="space-y-2 text-left w-max shrink-0">
                        <div><span class="font-semibold">Дата створення:</span> {{ $formatOrderDate($order->created_at) }}</div>
                        <div><span class="font-semibold">Користувач:</span> {{ $order->lastEditedBy?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full table-fixed text-sm border-b border-gray-200">
                    <thead style="background-color: #D3D4D4;">
                        <tr>
                            <th class="w-[70px] px-3 py-2 border text-center">№</th>
                            <th class="px-3 py-2 border text-left">Номенклатура</th>
                            <th class="w-[150px] px-3 py-2 border text-right">Кількість</th>
                            <th class="w-[190px] px-3 py-2 border text-right">Вартість за одн.</th>
                            <th class="w-[180px] px-3 py-2 border text-right">Сума</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td class="px-3 py-2 border text-center align-middle font-semibold">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2 border align-top whitespace-pre-wrap break-words">{{ trim((string) ($item['nomenclature'] ?? '—')) }}</td>
                                <td class="px-3 py-2 border text-right align-middle">{{ $formatOrderMoney($item['quantity'] ?? 0) }}</td>
                                <td class="px-3 py-2 border text-right align-middle">{{ $formatOrderMoney($item['unit_cost'] ?? 0) }}</td>
                                <td class="px-3 py-2 border text-right align-middle font-semibold">{{ $formatOrderMoney($item['sum'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 border text-center text-gray-500">Позиції замовлення відсутні.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if(count($items) > 0)
                    <div class="px-4 py-4 border-t">
                        <div class="ml-auto grid w-fit grid-cols-[max-content_100px] items-center gap-x-3 gap-y-2 text-sm">
                            <div class="text-left font-semibold text-gray-700">Сума з ПДВ</div>
                            <div class="text-right font-semibold text-gray-900">{{ $formatOrderMoney($order->total_cost) }}</div>

                            <div class="text-left font-semibold text-gray-700">Загальна сума сплат</div>
                            <div class="text-right font-semibold text-gray-900">{{ $formatOrderMoney($orderPaymentsTotal) }}</div>

                            <div class="col-span-2 h-4" aria-hidden="true"></div>

                            <div class="text-left text-base font-bold {{ $amountDueTextClass }}">Сума до сплати</div>
                            <div class="text-right text-base font-bold {{ $amountDueTextClass }}">{{ $formatOrderMoney($orderAmountDue) }}</div>
                        </div>
                    </div>
                @endif
            </div>

            @if($canUpdateOrder)
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <button
                    type="button"
                    @click="toggleOrderHistory()"
                    class="flex w-full items-center justify-between gap-3 border-b border-transparent bg-gray-50 px-4 py-3 text-left font-semibold text-gray-800 transition hover:bg-gray-100"
                    :class="historyOpen ? 'border-gray-200' : 'border-transparent'"
                    :aria-expanded="historyOpen"
                >
                    <span>Історія змін замовлення</span>
                    <svg class="h-5 w-5 text-gray-500 transition-transform" :class="historyOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div x-show="historyOpen" x-cloak>
                    <div x-show="historyLoading" class="px-4 py-8 text-center text-sm text-gray-500">
                        Завантаження історії змін…
                    </div>
                    <div x-show="historyError" x-cloak class="px-4 py-6 text-center">
                        <p class="text-sm text-red-700" x-text="historyError"></p>
                        <button type="button" @click="loadOrderHistory()" class="mt-3 rounded-md border border-red-300 bg-white px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                            Спробувати ще раз
                        </button>
                    </div>
                    <div x-show="historyLoaded && !historyLoading && !historyError" x-html="historyHtml"></div>
                </div>
            </div>
            @endif

            @if($canManageOrderPayments)
            <div x-show="showModal" x-cloak class="fixed inset-0 z-[14000] !mt-0 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50" @click="closeModal()"></div>
                <div class="relative max-h-[94vh] w-[1100px] max-w-full overflow-y-auto rounded-xl border border-gray-200 bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3
                                class="text-lg font-semibold text-gray-900"
                                x-text="isEditing ? 'Редагування платежу' : `Платежі замовлення ${orderNumber}`"
                            ></h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $order->client?->name ?? $order->customer_name }}</p>
                        </div>
                        <button type="button" @click="closeModal()" class="text-2xl leading-none text-gray-400 hover:text-gray-700" aria-label="Закрити">&times;</button>
                    </div>

                    <div x-show="paymentError" x-text="paymentError" class="mt-4 rounded-md bg-red-100 px-4 py-3 text-sm text-red-700"></div>

                    <div x-show="isEditing || canAddPayment" x-cloak x-ref="paymentEditor" class="mt-5 scroll-mt-4 rounded-lg border border-gray-200 bg-gray-50 p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h4 class="font-semibold text-gray-800" x-text="isEditing ? 'Редагування платежу' : 'Внести платіж'"></h4>
                            <button x-show="isEditing" x-cloak type="button" @click="resetForm()" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                Повернутися до платежів
                            </button>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-4" :class="isForeignCurrency() ? 'md:grid-cols-5' : 'md:grid-cols-4'">
                            <div>
                                <label for="order-payment-amount" class="block text-sm font-medium text-gray-700">Сума операції</label>
                                <input id="order-payment-amount" x-ref="paymentAmount" x-model="form.amount" @input="recalculateAmountUah()" type="text" inputmode="numeric" autocomplete="off" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0">
                            </div>
                            <div>
                                <label for="order-payment-currency" class="block text-sm font-medium text-gray-700">Валюта операції</label>
                                <select id="order-payment-currency" x-model="form.currency" @change="currencyChanged()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="UAH">UAH</option>
                                    <option value="USD" :disabled="!rates.USD" x-text="rateOption('USD')"></option>
                                    <option value="EUR" :disabled="!rates.EUR" x-text="rateOption('EUR')"></option>
                                </select>
                                <p x-show="ratesLoading" x-cloak class="mt-1 text-xs text-gray-500">Завантаження курсу…</p>
                                <p x-show="ratesError" x-text="ratesError" x-cloak class="mt-1 text-xs text-red-600"></p>
                            </div>
                            <div x-show="isForeignCurrency()" x-cloak>
                                <label for="order-payment-amount-uah" class="block text-sm font-medium text-gray-700">Сума списання (ГРН)</label>
                                <input id="order-payment-amount-uah" x-model="form.amountUah" type="text" inputmode="numeric" autocomplete="off" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0">
                                <p class="mt-1 text-xs text-gray-500" x-text="form.exchangeRate ? `SALE: ${formatRate(form.exchangeRate)} грн/${form.currency}` : ''"></p>
                            </div>
                            <div>
                                <label for="order-payment-date" class="block text-sm font-medium text-gray-700">Дата</label>
                                <input id="order-payment-date" x-model="form.date" type="date" :max="today" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="order-payment-time" class="block text-sm font-medium text-gray-700">Час</label>
                                <input id="order-payment-time" x-model="form.time" type="time" step="60" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="order-payment-comment" class="block text-sm font-medium text-gray-700">Коментар</label>
                            <textarea id="order-payment-comment" x-model="form.comment" rows="3" maxlength="2000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Додаткові відомості про платіж"></textarea>
                        </div>

                        <div class="mt-4 flex justify-end gap-3">
                            <button x-show="isEditing" x-cloak type="button" @click="resetForm()" :disabled="isSaving" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">Скасувати редагування</button>
                            <button x-show="!isEditing && overpaymentTotal > 0" x-cloak type="button" @click="submitPayment(true)" :disabled="isSaving" class="rounded-md border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50">
                                Списати з переплати
                            </button>
                            <button type="button" @click="submitPayment(isEditing ? null : false)" :disabled="isSaving" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-text="isSaving ? 'Збереження...' : (isEditing ? 'Зберегти зміни' : 'Внести платіж')"></span>
                            </button>
                        </div>
                    </div>

                    <div x-show="!isEditing && !canAddPayment" x-cloak class="mt-5 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                        Новий платіж недоступний: замовлення вже сплачено або має переплату.
                    </div>

                    <div x-show="isEditing && activeHistories.length > 0" x-cloak class="mt-5 rounded-lg border border-yellow-200 bg-yellow-50 p-5">
                        <h4 class="text-sm font-semibold uppercase text-gray-700">Історія змін платежу</h4>
                        <div class="mt-3 space-y-3">
                            <template x-for="(history, historyIndex) in activeHistories" :key="`order-payment-history-${historyIndex}`">
                                <div class="rounded-lg border border-yellow-200 bg-white p-4 text-sm">
                                    <div class="flex flex-wrap justify-between gap-2 font-semibold text-gray-800">
                                        <span x-text="history.user"></span>
                                        <span x-text="history.date"></span>
                                    </div>
                                    <div class="mt-2 space-y-2">
                                        <template x-for="(change, changeIndex) in history.changes" :key="`order-payment-change-${historyIndex}-${changeIndex}`">
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

                    <div x-show="!isEditing" x-cloak class="mt-6">
                        <h4 class="mb-3 text-sm font-semibold uppercase text-gray-700">Історія платежів замовлення</h4>
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full text-sm">
                                <thead style="background-color: #D3D4D4;">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Дата / час</th>
                                        <th class="px-3 py-2 text-left">№ замовлення</th>
                                        <th class="px-3 py-2 text-right">Сума</th>
                                        <th class="px-3 py-2 text-left">Валюта</th>
                                        <th class="px-3 py-2 text-left">Користувач</th>
                                        <th class="w-[130px] px-3 py-2 text-center">Дії</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($order->payments as $payment)
                                        <tr class="border-t border-gray-200 {{ $payment->is_edited ? 'bg-yellow-100' : 'bg-white' }}">
                                            <td class="px-3 py-2">{{ $payment->paid_at->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i') }}</td>
                                            <td class="px-3 py-2 font-medium">
                                                <div>{{ $order->order_number }}</div>
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
                                            <td class="px-3 py-2">грн</td>
                                            <td class="px-3 py-2">{{ $payment->createdBy?->name ?? '—' }}</td>
                                            <td class="px-3 py-2 text-center">
                                                <button type="button" @click="editPayment(@js($payment->public_id))" class="font-semibold text-indigo-600 hover:text-indigo-900">Редагувати</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-8 text-center text-gray-500">Платежі за цим замовленням ще не внесено.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <script>
        function orderPaymentPopup(config) {
            const emptyForm = () => ({
                amount: '',
                amountUah: '',
                calculatedAmountUah: '',
                currency: 'UAH',
                exchangeRate: null,
                date: config.today || '',
                time: config.currentTime || '',
                comment: '',
                fromOverpayment: false,
                updateUrl: '',
            });

            return {
                storeUrl: config.storeUrl || '',
                ratesUrl: config.ratesUrl || '',
                historyUrl: config.historyUrl || '',
                orderPublicId: config.orderPublicId || '',
                orderNumber: config.orderNumber || '',
                payments: Array.isArray(config.payments) ? config.payments : [],
                overpaymentTotal: Number(config.overpaymentTotal) || 0,
                canAddPayment: Boolean(config.canAddPayment),
                today: config.today || '',
                showModal: false,
                isEditing: false,
                isSaving: false,
                paymentError: '',
                rates: {},
                ratesFetchedAt: '',
                ratesLoading: false,
                ratesError: '',
                historyOpen: false,
                historyLoaded: false,
                historyLoading: false,
                historyError: '',
                historyHtml: '',
                form: emptyForm(),
                activeHistories: [],

                init() {
                    if (config.openOnLoad) {
                        this.showModal = true;
                        this.loadRates();
                    }
                },

                openModal() {
                    this.resetForm();
                    this.showModal = true;
                    this.loadRates();
                },

                closeModal() {
                    if (!this.isSaving) {
                        this.showModal = false;
                        this.paymentError = '';
                    }
                },

                resetForm() {
                    this.isEditing = false;
                    this.paymentError = '';
                    this.form = emptyForm();
                    this.activeHistories = [];
                },

                editPayment(paymentId) {
                    const payment = this.payments.find((item) => item.id === paymentId);
                    if (!payment) {
                        return;
                    }

                    this.isEditing = true;
                    this.paymentError = '';
                    this.form = {
                        amount: String(payment.amount || ''),
                        amountUah: String(payment.amountUah || payment.amount || ''),
                        calculatedAmountUah: String(payment.calculatedAmountUah || ''),
                        currency: payment.currency || 'UAH',
                        exchangeRate: payment.exchangeRate ? Number(payment.exchangeRate) : null,
                        date: payment.date || this.today,
                        time: payment.time || '',
                        comment: payment.comment || '',
                        fromOverpayment: Boolean(payment.fromOverpayment),
                        updateUrl: payment.updateUrl || '',
                    };
                    this.activeHistories = Array.isArray(payment.histories) ? payment.histories : [];
                    this.loadRates();
                    this.$nextTick(() => {
                        this.$refs.paymentEditor?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        window.setTimeout(() => this.$refs.paymentAmount?.focus({ preventScroll: true }), 250);
                    });
                },

                validate() {
                    const amount = String(this.form.amount || '').trim();
                    if (!/^\d+$/.test(amount) || Number.parseInt(amount, 10) <= 0) {
                        return 'Сума повинна бути цілим числом більше нуля.';
                    }
                    if (!['UAH', 'USD', 'EUR'].includes(this.form.currency)) {
                        return 'Оберіть доступну валюту.';
                    }
                    if (this.form.fromOverpayment && this.form.currency !== 'UAH') {
                        return 'Списання з переплати доступне лише у гривні.';
                    }
                    if (this.isForeignCurrency()) {
                        const amountUah = String(this.form.amountUah || '').trim();
                        if (!this.form.exchangeRate || !/^\d+$/.test(amountUah) || Number.parseInt(amountUah, 10) <= 0) {
                            return 'Вкажіть суму списання у гривні цілим числом більше нуля.';
                        }
                    }
                    if (!this.form.date || this.form.date > this.today) {
                        return 'Оберіть поточну або минулу дату.';
                    }
                    if (!/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(this.form.time || '')) {
                        return 'Вкажіть коректний час у форматі гг:хх.';
                    }
                    if (this.form.fromOverpayment && !this.isEditing && Number.parseInt(amount, 10) > this.overpaymentTotal) {
                        return 'Сума списання перевищує доступну переплату клієнта.';
                    }

                    return '';
                },

                async submitPayment(fromOverpayment = null) {
                    if (this.isSaving) {
                        return;
                    }
                    if (!this.isEditing && !this.canAddPayment) {
                        this.paymentError = 'Новий платіж недоступний: замовлення вже сплачено або має переплату.';
                        return;
                    }

                    if (!this.isEditing && typeof fromOverpayment === 'boolean') {
                        this.form.fromOverpayment = fromOverpayment;
                    }

                    this.paymentError = this.validate();
                    if (this.paymentError) {
                        return;
                    }

                    const conversionDetails = this.isForeignCurrency()
                        ? `\nКурс SALE ПриватБанку: ${this.formatRate(this.form.exchangeRate)} грн/${this.form.currency}.\nСума списання: ${this.form.amountUah} грн.`
                        : '';
                    const overpaymentWarning = this.form.fromOverpayment
                        ? '\nСума платежу буде списана з переплати клієнта.'
                        : '';
                    const confirmation = (this.isEditing
                        ? 'Підтверджуєте, що всі зміни платежу внесено правильно?'
                        : 'Підтверджуєте, що всі дані платежу внесено правильно?') + conversionDetails + overpaymentWarning;
                    if (!window.confirm(confirmation)) {
                        return;
                    }

                    this.isSaving = true;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const response = await fetch(this.isEditing ? this.form.updateUrl : this.storeUrl, {
                            method: this.isEditing ? 'PATCH' : 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify({
                                amount: Number.parseInt(this.form.amount, 10),
                                amount_uah: this.isForeignCurrency() ? Number.parseInt(this.form.amountUah, 10) : Number.parseInt(this.form.amount, 10),
                                currency: this.form.currency,
                                payment_date: this.form.date,
                                payment_time: this.form.time,
                                payment_type: 'order',
                                payment_source: this.form.fromOverpayment ? 'overpayment' : 'direct',
                                order_public_id: this.orderPublicId,
                                comment: String(this.form.comment || '').trim() || null,
                                return_context: 'order',
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
                        this.isSaving = false;
                    }
                },

                isForeignCurrency() {
                    return ['USD', 'EUR'].includes(this.form.currency);
                },

                formatRate(rate) {
                    return Number(rate || 0).toLocaleString('uk-UA', { minimumFractionDigits: 2, maximumFractionDigits: 6 });
                },

                rateOption(currency) {
                    return this.rates[currency]
                        ? `${currency} — ${this.formatRate(this.rates[currency])} грн`
                        : `${currency} — курс недоступний`;
                },

                currencyChanged() {
                    if (!this.isForeignCurrency()) {
                        this.form.amountUah = this.form.amount;
                        this.form.calculatedAmountUah = this.form.amount;
                        this.form.exchangeRate = null;
                        return;
                    }

                    this.form.exchangeRate = Number(this.rates[this.form.currency] || 0) || null;
                    this.recalculateAmountUah();
                },

                recalculateAmountUah() {
                    if (!this.isForeignCurrency()) {
                        this.form.amountUah = this.form.amount;
                        return;
                    }

                    const amount = Number.parseInt(String(this.form.amount || ''), 10);
                    const rate = Number(this.rates[this.form.currency] || this.form.exchangeRate || 0);
                    if (!Number.isInteger(amount) || amount <= 0 || rate <= 0) {
                        this.form.amountUah = '';
                        this.form.calculatedAmountUah = '';
                        return;
                    }

                    const calculated = Math.ceil(amount * rate);
                    this.form.exchangeRate = rate;
                    this.form.calculatedAmountUah = String(calculated);
                    this.form.amountUah = String(calculated);
                },

                async loadRates() {
                    if (this.ratesLoading || !this.ratesUrl) {
                        return;
                    }

                    this.ratesLoading = true;
                    this.ratesError = '';
                    try {
                        const response = await fetch(this.ratesUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                        const payload = await response.json();
                        if (!response.ok || !payload?.ok) {
                            throw new Error(payload?.message || 'Не вдалося завантажити курс валют.');
                        }
                        this.rates = payload.rates || {};
                        this.ratesFetchedAt = payload.fetched_at || '';
                    } catch (error) {
                        this.ratesError = error?.message || 'Не вдалося завантажити курс валют.';
                    } finally {
                        this.ratesLoading = false;
                    }
                },

                toggleOrderHistory() {
                    this.historyOpen = !this.historyOpen;
                    if (this.historyOpen && !this.historyLoaded) {
                        this.loadOrderHistory();
                    }
                },

                async loadOrderHistory() {
                    if (this.historyLoading || !this.historyUrl) {
                        return;
                    }

                    this.historyLoading = true;
                    this.historyError = '';
                    try {
                        const response = await fetch(this.historyUrl, {
                            method: 'GET',
                            headers: { 'Accept': 'application/json' },
                            cache: 'no-store',
                        });
                        const payload = await response.json();
                        if (!response.ok || !payload?.ok) {
                            throw new Error(payload?.message || 'Не вдалося завантажити історію змін замовлення.');
                        }

                        this.historyHtml = payload.html || '';
                        this.historyLoaded = true;
                    } catch (error) {
                        this.historyError = error?.message || 'Не вдалося завантажити історію змін замовлення.';
                    } finally {
                        this.historyLoading = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
