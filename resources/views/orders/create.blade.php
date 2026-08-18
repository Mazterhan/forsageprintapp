<x-app-layout>
    @php
        $order = $order ?? null;
        $isEdit = $order !== null;
        $pageTitle = $isEdit
            ? __('Редагування замовлення :number', ['number' => $order->order_number])
            : __('Створити замовлення для');
        $saveUrl = $isEdit ? route('orders.update', $order) : route('orders.store');
        $backUrl = $isEdit ? route('orders.show', $order) : route('orders.index');

        if ($isEdit && (float) $order->payments_total > (float) $order->total_cost) {
            $initialPaymentStatus = ['label' => 'Є переплата', 'className' => 'border-blue-400 bg-teal-100 text-blue-800'];
        } elseif ($isEdit && abs((float) $order->payments_total - (float) $order->total_cost) < 0.005 && (float) $order->total_cost > 0) {
            $initialPaymentStatus = ['label' => 'Сплачено', 'className' => 'border-green-300 bg-green-100 text-green-800'];
        } elseif ($isEdit && (float) $order->payments_total > 0) {
            $initialPaymentStatus = ['label' => 'Частково сплачено', 'className' => 'border-orange-500 bg-yellow-100 text-orange-800'];
        } else {
            $initialPaymentStatus = [
                'label' => $isEdit ? 'Не сплачено' : 'Статус оплати',
                'className' => $isEdit ? 'border-red-300 bg-rose-100 text-red-800' : 'border-gray-300 bg-gray-100 text-gray-700',
            ];
        }
    @endphp

    @section('title', $pageTitle)

    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-4">
            @if ($isEdit)
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $pageTitle }}
                </h2>
            @else
                <div
                    id="order-create-client-picker"
                    x-data="orderCreateForm({ clients: @js($clients) })"
                    class="flex min-w-0 flex-1 flex-wrap items-center gap-4"
                >
                    <h2 class="shrink-0 font-semibold text-xl text-gray-800 leading-tight">
                        {{ $pageTitle }}
                    </h2>

                    <div
                        class="relative z-[10000] w-full min-w-[280px] max-w-[520px] flex-1 overflow-visible"
                        @click.outside="showClientDropdown = false; clientDropdownActiveIndex = -1"
                    >
                        <input type="hidden" name="client_id" :value="selectedClientId">
                        <div class="relative overflow-hidden rounded-md">
                            <input
                                id="order-customer-name"
                                name="customer_name"
                                x-model="selectedClientQuery"
                                @input="onClientInputChanged(); showClientDropdown = true"
                                @focus="showClientDropdown = true; syncClientDropdownActiveIndex()"
                                @keydown.arrow-down.prevent="moveClientDropdown(1)"
                                @keydown.arrow-up.prevent="moveClientDropdown(-1)"
                                @keydown.enter.prevent="selectActiveClient()"
                                @keydown.escape="showClientDropdown = false; clientDropdownActiveIndex = -1"
                                @blur="handleClientInputBlur()"
                                type="text"
                                autocomplete="off"
                                class="block w-full rounded-md border-gray-300 pr-10 text-left shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Оберіть замовника"
                            >
                            <button
                                type="button"
                                @click="showClientDropdown = !showClientDropdown; if (showClientDropdown) syncClientDropdownActiveIndex()"
                                class="absolute inset-y-0 right-0 z-10 flex w-10 items-center justify-center rounded-r-md border border-gray-300 bg-white text-gray-500 hover:text-gray-700"
                                aria-label="Відкрити список замовників"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.118l3.71-3.887a.75.75 0 111.08 1.04l-4.25 4.455a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <div
                            x-show="showClientDropdown"
                            x-transition
                            x-cloak
                            class="absolute z-[10001] mt-1 max-h-64 w-full overflow-auto rounded-md border border-gray-300 bg-white text-left shadow-lg"
                        >
                            <template x-if="getFilteredClients().length === 0">
                                <div class="px-3 py-2 text-sm text-gray-500">Нічого не знайдено</div>
                            </template>
                            <template x-for="(client, clientIndex) in getFilteredClients()" :key="`client-option-${client.id}`">
                                <button
                                    type="button"
                                    @mousedown.prevent="selectClient(client)"
                                    class="flex w-full justify-start px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                    :class="clientDropdownActiveIndex === clientIndex ? 'bg-indigo-50 text-indigo-800' : ''"
                                    x-text="client.name"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </x-slot>

    <div class="py-12">
        <div
            x-data="orderCreateForm({
                clients: @js($clients),
                saveUrl: @js($saveUrl),
                appendCandidateUrl: @js($isEdit ? '' : route('orders.append-candidate')),
                saveMethod: @js($isEdit ? 'PATCH' : 'POST'),
                isEdit: @js($isEdit),
                initialClientId: @js($order?->client_id),
                initialClientName: @js($order?->customer_name ?? ''),
                initialItems: @js($order?->items ?? []),
                initialOrderStatus: @js($order?->status ?? \App\Models\Order::STATUS_NEW),
                paymentsTotal: @js((float) ($order?->payments_total ?? 0)),
                paymentStatus: @js($initialPaymentStatus),
            })"
            @order-create-client-changed.window="handleClientSelectionChanged($event.detail)"
            @resize.window.debounce.100ms="resizeAllItemTextFields()"
            @beforeunload.window="warnAboutUnsavedStatus($event)"
            class="max-w-[1700px] mx-auto space-y-5 px-6 sm:px-8 lg:px-12"
        >
            @if ($isEdit)
                <div
                    class="relative overflow-visible rounded-lg border border-gray-300 p-4 shadow-sm"
                    style="background-color: #FCEEDF;"
                >
                <div class="flex flex-wrap items-end gap-4">
                    <div class="min-w-[320px] flex-1">
                        <div
                            class="relative z-[10000] overflow-visible"
                            @click.outside="showClientDropdown = false; clientDropdownActiveIndex = -1"
                        >
                            <input type="hidden" name="client_id" :value="selectedClientId">
                            <div class="relative overflow-hidden rounded-md">
                                <input
                                    id="order-customer-name"
                                    name="customer_name"
                                    x-model="selectedClientQuery"
                                    @input="onClientInputChanged(); showClientDropdown = true"
                                    @focus="showClientDropdown = true; syncClientDropdownActiveIndex()"
                                    @keydown.arrow-down.prevent="moveClientDropdown(1)"
                                    @keydown.arrow-up.prevent="moveClientDropdown(-1)"
                                    @keydown.enter.prevent="selectActiveClient()"
                                    @keydown.escape="showClientDropdown = false; clientDropdownActiveIndex = -1"
                                    @blur="handleClientInputBlur()"
                                    type="text"
                                    autocomplete="off"
                                    @readonly($isEdit)
                                    class="block w-full rounded-md border-gray-300 {{ $isEdit ? 'cursor-not-allowed bg-gray-100 pr-3 text-gray-600' : 'pr-10' }} text-left shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Оберіть замовника"
                                >
                                @unless($isEdit)
                                <button
                                    type="button"
                                    @click="showClientDropdown = !showClientDropdown; if (showClientDropdown) syncClientDropdownActiveIndex()"
                                    class="absolute inset-y-0 right-0 z-10 flex w-10 items-center justify-center rounded-r-md border-l border-gray-200 bg-white text-gray-500 hover:text-gray-700"
                                    aria-label="Відкрити список замовників"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.118l3.71-3.887a.75.75 0 111.08 1.04l-4.25 4.455a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                @endunless
                            </div>

                            @unless($isEdit)
                            <div
                                x-show="showClientDropdown"
                                x-transition
                                x-cloak
                                class="absolute z-[10001] mt-1 max-h-64 w-full overflow-auto rounded-md border border-gray-300 bg-white text-left shadow-lg"
                            >
                                <template x-if="getFilteredClients().length === 0">
                                    <div class="px-3 py-2 text-sm text-gray-500">Нічого не знайдено</div>
                                </template>
                                <template x-for="(client, clientIndex) in getFilteredClients()" :key="`client-option-${client.id}`">
                                    <button
                                        type="button"
                                        @mousedown.prevent="selectClient(client)"
                                        class="flex w-full justify-start px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                        :class="clientDropdownActiveIndex === clientIndex ? 'bg-indigo-50 text-indigo-800' : ''"
                                        x-text="client.name"
                                    ></button>
                                </template>
                            </div>
                            @endunless
                        </div>
                    </div>

                    <div class="w-[158px]">
                        <select
                            x-model="orderStatus"
                            aria-label="Статус замовлення"
                            :class="orderStatusClass(orderStatus)"
                            class="block h-[42px] w-full appearance-none rounded-md border px-2 py-2 text-center text-sm font-semibold shadow-sm focus:ring-1"
                            style="appearance: none; background-image: none;"
                        >
                            @foreach(\App\Models\Order::selectableStatuses() as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}">{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-[158px]">
                        <input
                            id="order-payment-status"
                            type="text"
                            readonly
                            :value="paymentStatus.label"
                            :class="paymentStatus.className"
                            class="block h-[42px] w-full cursor-default rounded-md border px-2 py-2 text-center text-sm font-semibold shadow-sm focus:ring-0"
                        >
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <button
                            type="button"
                            title="Вивантажити замовлення у PDF"
                            aria-label="Вивантажити замовлення у PDF"
                            class="inline-flex h-[42px] w-[42px] items-center justify-center rounded-md border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M8 15h8M8 18h5" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-[42px] items-center rounded-md border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                        >
                            Платежі
                        </button>
                    </div>
                </div>
                </div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-fixed border border-gray-200 text-sm">
                            <thead>
                                <tr style="background-color: #FCEEDF;">
                                    <th class="w-[70px] border-b border-r border-gray-200 px-3 py-3 text-center">№</th>
                                    <th class="border-b border-r border-gray-200 px-3 py-3 text-left">Номенклатура</th>
                                    <th class="w-[220px] border-b border-r border-gray-200 px-3 py-3 text-left">Опис</th>
                                    <th class="w-[150px] border-b border-r border-gray-200 px-3 py-3 text-right">Кількість</th>
                                    <th class="w-[190px] border-b border-r border-gray-200 px-3 py-3 text-right">Вартість за одн.</th>
                                    <th class="w-[180px] border-b border-gray-200 px-3 py-3 text-right">Сума</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, itemIndex) in orderItems" :key="item.uid">
                                    <tr :class="itemIndex % 2 === 0 ? 'bg-gray-50' : 'bg-white'">
                                        <td class="border-b border-r border-gray-200 px-2 py-3 text-center align-middle font-semibold">
                                            <div class="flex items-center justify-center gap-2">
                                                <span x-text="itemIndex + 1"></span>
                                                <button
                                                    x-show="isEdit && hasAnyOrderItemValue(item)"
                                                    type="button"
                                                    @click="removeOrderItem(itemIndex)"
                                                    class="text-lg leading-none text-red-500 hover:text-red-700"
                                                    title="Видалити позицію"
                                                    aria-label="Видалити позицію"
                                                >&times;</button>
                                            </div>
                                        </td>
                                        <td class="border-b border-r border-gray-200 p-2 align-top">
                                            <textarea
                                                x-model="item.nomenclature"
                                                data-order-nomenclature
                                                :name="`items[${itemIndex}][nomenclature]`"
                                                @input="onNomenclatureInput(item, $event)"
                                                maxlength="500"
                                                rows="1"
                                                placeholder="Введіть номенклатуру"
                                                class="block min-h-[42px] w-full resize-none overflow-hidden rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            ></textarea>
                                        </td>
                                        <td class="w-[220px] border-b border-r border-gray-200 p-2 align-middle">
                                            <textarea
                                                x-model="item.description"
                                                data-order-description
                                                :name="`items[${itemIndex}][description]`"
                                                @input="onDescriptionInput(item, $event)"
                                                maxlength="200"
                                                rows="1"
                                                class="block min-h-[42px] w-full resize-none overflow-hidden rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            ></textarea>
                                            <p
                                                x-show="String(item.description || '').length >= 180"
                                                x-cloak
                                                class="mt-1 text-xs font-semibold"
                                                :class="String(item.description || '').length >= 200 ? 'text-red-600' : 'text-amber-600'"
                                                x-text="String(item.description || '').length >= 200
                                                    ? 'Досягнуто максимальний ліміт: 200/200'
                                                    : `Залишилось символів: ${200 - String(item.description || '').length}`"
                                            ></p>
                                        </td>
                                        <td class="border-b border-r border-gray-200 p-2 align-middle">
                                            <input
                                                x-model="item.quantity"
                                                :name="`items[${itemIndex}][quantity]`"
                                                @input="sanitizePositiveInteger(item, 'quantity', $event)"
                                                type="text"
                                                inputmode="numeric"
                                                autocomplete="off"
                                                placeholder="0"
                                                class="block h-[42px] w-full rounded-md border-gray-300 text-right shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                        </td>
                                        <td class="border-b border-r border-gray-200 p-2 align-middle">
                                            <input
                                                x-model="item.unitCost"
                                                :name="`items[${itemIndex}][unit_cost]`"
                                                @input="sanitizePositiveInteger(item, 'unitCost', $event)"
                                                type="text"
                                                inputmode="numeric"
                                                autocomplete="off"
                                                placeholder="0"
                                                class="block h-[42px] w-full rounded-md border-gray-300 text-right shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                        </td>
                                        <td class="border-b border-gray-200 px-3 py-3 text-right align-middle font-semibold" x-text="formatInteger(getItemSum(item))"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div
                        x-show="hasNomenclatureItem()"
                        x-cloak
                        class="ml-auto mt-6 grid w-fit grid-cols-[max-content_100px] items-center gap-x-3 gap-y-2 text-sm"
                    >
                        <div class="text-left font-semibold text-gray-700">Сума з ПДВ</div>
                        <div class="text-right font-semibold text-gray-900" x-text="formatInteger(getOrderTotal())"></div>

                        <div class="text-left font-semibold text-gray-700">Загальна сума сплат</div>
                        <div class="text-right font-semibold text-gray-900" x-text="formatInteger(paymentsTotal)"></div>

                        <div class="col-span-2 h-4" aria-hidden="true"></div>

                        <div class="text-left text-base font-bold text-gray-900">Сума до сплати</div>
                        <div class="text-right text-base font-bold text-gray-900" x-text="formatInteger(getAmountDue())"></div>
                    </div>
                </div>
            </div>

            <div
                x-show="hasNomenclatureItem() && (isEdit || hasCustomerName())"
                x-cloak
                class="rounded-lg border border-gray-300 p-4 shadow-sm"
                style="background-color: #FCEEDF;"
            >
                <div class="flex justify-end gap-3">
                    @if($isEdit)
                        <a
                            href="{{ $backUrl }}"
                            data-order-edit-cancel
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Скасувати
                        </a>
                    @else
                        <button
                            x-show="appendCandidate"
                            x-cloak
                            type="button"
                            @click="requestAppendToExistingOrder()"
                            :disabled="isSaving || isCheckingAppendCandidate"
                            class="inline-flex items-center rounded-md border border-indigo-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span x-text="isSaving ? 'Збереження...' : 'Додати до існуючого замовлення'"></span>
                        </button>
                    @endif
                    <button
                        type="button"
                        @click="requestSaveOrder()"
                        :disabled="isSaving"
                        class="inline-flex items-center rounded-md border border-transparent px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                        style="background-color: #698DE3;"
                    >
                        <span x-text="isSaving ? 'Збереження...' : (isEdit ? 'Зберегти' : 'Створити замовлення')"></span>
                    </button>
                </div>
            </div>

            <div
                x-show="showWarningModal"
                x-cloak
                class="fixed inset-0 z-[12000] !mt-0 flex items-center justify-center p-4"
            >
                <div class="absolute inset-0 bg-black/40" @click="showWarningModal = false"></div>
                <div class="relative w-[430px] max-w-full rounded-lg border border-gray-300 p-6 shadow-xl" style="background-color: #E0E0E0;">
                    <p class="text-base font-semibold text-gray-900" x-text="warningMessage"></p>
                    <div class="mt-6 flex justify-center">
                        <button
                            type="button"
                            @click="showWarningModal = false"
                            class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700"
                        >
                            Зрозуміло
                        </button>
                    </div>
                </div>
            </div>

            <div
                x-show="showCreateClientModal"
                x-cloak
                class="fixed inset-0 z-[12000] !mt-0 flex items-center justify-center p-4"
            >
                <div class="absolute inset-0 bg-black/40" @click="showCreateClientModal = false"></div>
                <div class="relative w-[500px] max-w-full rounded-lg border border-gray-300 p-6 shadow-xl" style="background-color: #E0E0E0;">
                    <p class="text-base font-semibold text-gray-900">Замовника не знайдено</p>
                    <p class="mt-2 text-sm text-gray-800">
                        Замовника <span class="font-semibold" x-text="selectedClientQuery"></span> не існує. Створити нового замовника та зберегти замовлення?
                    </p>
                    <div class="mt-6 flex items-center justify-center gap-3">
                        <button
                            type="button"
                            @click="confirmCreateClientAndSave()"
                            :disabled="isSaving"
                            class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            Створити замовника
                        </button>
                        <button
                            type="button"
                            @click="showCreateClientModal = false"
                            :disabled="isSaving"
                            class="inline-flex items-center rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200 disabled:opacity-50"
                        >
                            Скасувати
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function orderCreateForm(config) {
            return {
                clients: config.clients || [],
                saveUrl: config.saveUrl || '',
                appendCandidateUrl: config.appendCandidateUrl || '',
                saveMethod: config.saveMethod || 'POST',
                isEdit: Boolean(config.isEdit),
                selectedClientId: config.initialClientId ? String(config.initialClientId) : '',
                selectedClientQuery: config.initialClientName || '',
                initialOrderStatus: config.initialOrderStatus || 'new',
                orderStatus: config.initialOrderStatus || 'new',
                showClientDropdown: false,
                clientDropdownActiveIndex: -1,
                paymentStatus: config.paymentStatus || {
                    label: 'Статус оплати',
                    className: 'border-gray-300 bg-gray-100 text-gray-700',
                },
                paymentsTotal: Number(config.paymentsTotal) || 0,
                initialItems: Array.isArray(config.initialItems) ? config.initialItems : [],
                orderItems: [],
                isSaving: false,
                isCheckingAppendCandidate: false,
                appendCandidate: null,
                appendCandidateTimer: null,
                appendCandidateRequestId: 0,
                warningMessage: '',
                showWarningModal: false,
                showCreateClientModal: false,

                get statusDirty() {
                    return this.isEdit && this.orderStatus !== this.initialOrderStatus;
                },

                orderStatusClass(status) {
                    if (status === 'blocked') {
                        return 'border-orange-500 bg-yellow-100 text-orange-800 focus:border-orange-500 focus:ring-orange-400';
                    }
                    if (status === 'completed') {
                        return 'border-green-300 bg-green-100 text-green-800 focus:border-green-400 focus:ring-green-300';
                    }
                    if (status === 'cancelled') {
                        return 'border-gray-400 bg-gray-100 text-gray-700 focus:border-gray-500 focus:ring-gray-300';
                    }

                    return 'border-blue-400 bg-teal-100 text-blue-800 focus:border-blue-400 focus:ring-blue-300';
                },

                warnAboutUnsavedStatus(event) {
                    if (!this.statusDirty) {
                        return;
                    }

                    event.preventDefault();
                    event.returnValue = '';
                },

                init() {
                    this.orderItems = this.initialItems.map((item) => this.createOrderItem(item));
                    if (this.orderItems.length === 0) {
                        this.orderItems = [this.createOrderItem()];
                    }
                    this.ensureBlankOrderItem();
                    this.resizeAllItemTextFields();
                },

                createOrderItem(source = {}) {
                    return {
                        uid: source.item_id || `${Date.now()}-${Math.random().toString(16).slice(2)}`,
                        itemId: source.item_id || '',
                        nomenclature: source.nomenclature || '',
                        description: source.description || '',
                        quantity: source.quantity ? String(source.quantity) : '1',
                        unitCost: source.unit_cost ? String(source.unit_cost) : '',
                    };
                },

                removeOrderItem(itemIndex) {
                    this.orderItems.splice(itemIndex, 1);
                    if (this.orderItems.length === 0) {
                        this.orderItems.push(this.createOrderItem());
                    }
                    this.ensureBlankOrderItem();
                    this.resizeAllItemTextFields();
                },

                onNomenclatureInput(item, event) {
                    item.nomenclature = String(event.currentTarget.value || '').slice(0, 500);
                    event.currentTarget.value = item.nomenclature;
                    this.resizeItemTextarea(event.currentTarget);
                    this.ensureBlankOrderItem();
                },

                onDescriptionInput(item, event) {
                    item.description = String(event.currentTarget.value || '').slice(0, 200);
                    event.currentTarget.value = item.description;
                    this.resizeItemTextarea(event.currentTarget);
                },

                resizeItemTextarea(textarea) {
                    if (!textarea) {
                        return;
                    }

                    textarea.style.height = '0px';
                    const styles = window.getComputedStyle(textarea);
                    const borderHeight = (Number.parseFloat(styles.borderTopWidth) || 0)
                        + (Number.parseFloat(styles.borderBottomWidth) || 0);
                    const requiredHeight = Math.ceil(textarea.scrollHeight + borderHeight + 2);
                    textarea.style.height = `${Math.max(42, requiredHeight)}px`;
                },

                resizeAllItemTextFields() {
                    this.$nextTick(() => {
                        this.$root.querySelectorAll('[data-order-nomenclature], [data-order-description]').forEach((textarea) => {
                            this.resizeItemTextarea(textarea);
                        });
                    });
                },

                sanitizePositiveInteger(item, field, event) {
                    const digits = String(event.currentTarget.value || '').replace(/\D+/g, '');
                    const normalized = digits.replace(/^0+/, '');
                    item[field] = normalized === '' ? '' : String(Number.parseInt(normalized, 10));
                    event.currentTarget.value = item[field];
                    this.ensureBlankOrderItem();
                },

                isOrderItemComplete(item) {
                    return String(item.nomenclature || '').trim() !== ''
                        && Number.parseInt(item.quantity, 10) > 0
                        && Number.parseInt(item.unitCost, 10) > 0;
                },

                ensureBlankOrderItem() {
                    const lastItem = this.orderItems[this.orderItems.length - 1];
                    if (lastItem && this.isOrderItemComplete(lastItem)) {
                        this.orderItems.push(this.createOrderItem());
                    }
                },

                getItemSum(item) {
                    const quantity = Number.parseInt(item.quantity, 10) || 0;
                    const unitCost = Number.parseInt(item.unitCost, 10) || 0;
                    return quantity * unitCost;
                },

                getOrderTotal() {
                    return this.orderItems.reduce((total, item) => total + this.getItemSum(item), 0);
                },

                getAmountDue() {
                    return this.getOrderTotal() - this.paymentsTotal;
                },

                formatInteger(value) {
                    return new Intl.NumberFormat('uk-UA', {
                        maximumFractionDigits: 0,
                    }).format(Number(value) || 0);
                },

                getEnteredOrderItems() {
                    return this.orderItems.filter((item) => this.hasAnyOrderItemValue(item));
                },

                hasAnyOrderItemValue(item) {
                    return String(item.nomenclature || '').trim() !== ''
                        || String(item.description || '').trim() !== ''
                        || String(item.unitCost || '').trim() !== '';
                },

                hasNomenclatureItem() {
                    return this.orderItems.some((item) => String(item.nomenclature || '').trim() !== '');
                },

                hasCustomerName() {
                    return String(this.selectedClientQuery || '').trim() !== '';
                },

                showWarning(message) {
                    this.warningMessage = message;
                    this.showWarningModal = true;
                },

                requestSaveOrder() {
                    if (this.isSaving) {
                        return;
                    }

                    if (!this.isEdit) {
                        const clientPicker = document.getElementById('order-create-client-picker');
                        this.selectedClientId = clientPicker?.querySelector('input[name="client_id"]')?.value || '';
                        this.selectedClientQuery = clientPicker?.querySelector('input[name="customer_name"]')?.value || '';
                    }

                    const customerName = String(this.selectedClientQuery || '').trim();
                    if (customerName === '') {
                        this.showWarning('Неможливо створити замовлення. Оберіть замовника.');
                        return;
                    }

                    const enteredItems = this.getEnteredOrderItems();
                    if (enteredItems.length === 0) {
                        this.showWarning('Неможливо створити замовлення. Додайте хоча б одну позицію.');
                        return;
                    }

                    if (enteredItems.some((item) => !this.isOrderItemComplete(item))) {
                        this.showWarning('Заповніть номенклатуру, кількість і вартість для кожної позиції.');
                        return;
                    }

                    const exactClient = this.clients.find((client) =>
                        this.normalizeForCompare(client.name) === this.normalizeForCompare(customerName)
                    );
                    if (exactClient) {
                        this.selectedClientId = String(exactClient.id);
                    }

                    if (!this.selectedClientId) {
                        this.showCreateClientModal = true;
                        return;
                    }

                    this.saveOrderWithConfirmation(false);
                },

                requestAppendToExistingOrder() {
                    if (this.isSaving || !this.appendCandidate?.append_url) {
                        return;
                    }

                    const enteredItems = this.getEnteredOrderItems();
                    if (enteredItems.length === 0) {
                        this.showWarning('Неможливо додати позиції. Додайте хоча б одну позицію.');
                        return;
                    }
                    if (enteredItems.some((item) => !this.isOrderItemComplete(item))) {
                        this.showWarning('Заповніть номенклатуру, кількість і вартість для кожної позиції.');
                        return;
                    }
                    if (!window.confirm(`Позиції буде додано до замовлення ${this.appendCandidate.number}. Підтверджуєте дію?`)) {
                        return;
                    }

                    this.appendItemsToExistingOrder(enteredItems);
                },

                async appendItemsToExistingOrder(enteredItems) {
                    this.isSaving = true;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const response = await fetch(this.appendCandidate.append_url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify({
                                items: enteredItems.map((item) => ({
                                    nomenclature: String(item.nomenclature || '').trim(),
                                    description: String(item.description || '').trim(),
                                    quantity: Number.parseInt(item.quantity, 10),
                                    unit_cost: Number.parseInt(item.unitCost, 10),
                                })),
                            }),
                        });
                        const payload = await response.json();
                        if (!response.ok || !payload?.ok) {
                            throw new Error(payload?.message || 'Не вдалося додати позиції до існуючого замовлення.');
                        }
                        if (payload.redirect_url) {
                            window.location.href = payload.redirect_url;
                        }
                    } catch (error) {
                        this.showWarning(error?.message || 'Не вдалося додати позиції до існуючого замовлення.');
                        this.checkAppendCandidate();
                    } finally {
                        this.isSaving = false;
                    }
                },

                handleClientSelectionChanged(detail = {}) {
                    if (this.isEdit) {
                        return;
                    }

                    this.selectedClientId = detail.clientId ? String(detail.clientId) : '';
                    this.selectedClientQuery = String(detail.clientName || '');
                    this.appendCandidate = null;
                    window.clearTimeout(this.appendCandidateTimer);
                    this.appendCandidateTimer = window.setTimeout(() => this.checkAppendCandidate(), 250);
                },

                async checkAppendCandidate() {
                    const clientId = Number.parseInt(this.selectedClientId, 10);
                    if (!clientId || !this.appendCandidateUrl) {
                        this.appendCandidate = null;
                        return;
                    }

                    const requestId = ++this.appendCandidateRequestId;
                    this.isCheckingAppendCandidate = true;
                    try {
                        const url = new URL(this.appendCandidateUrl, window.location.origin);
                        url.searchParams.set('client_id', String(clientId));
                        const response = await fetch(url.toString(), {
                            headers: { 'Accept': 'application/json' },
                        });
                        const payload = await response.json();
                        if (requestId !== this.appendCandidateRequestId) {
                            return;
                        }
                        this.appendCandidate = response.ok && payload?.ok ? payload.order : null;
                    } catch (error) {
                        if (requestId === this.appendCandidateRequestId) {
                            this.appendCandidate = null;
                        }
                    } finally {
                        if (requestId === this.appendCandidateRequestId) {
                            this.isCheckingAppendCandidate = false;
                        }
                    }
                },

                confirmCreateClientAndSave() {
                    if (!this.confirmOrderSave()) {
                        return;
                    }

                    this.showCreateClientModal = false;
                    this.saveOrder(true);
                },

                saveOrderWithConfirmation(createClient) {
                    if (!this.confirmOrderSave()) {
                        return;
                    }

                    this.saveOrder(createClient);
                },

                confirmOrderSave() {
                    const message = this.isEdit
                        ? 'Замовлення буде збережено з внесеними змінами. Підтверджуєте збереження змін?'
                        : 'Замовлення буде створено з введеними даними. Підтверджуєте створення замовлення?';

                    return window.confirm(message);
                },

                async saveOrder(createClient) {
                    if (this.isSaving || !this.saveUrl) {
                        return;
                    }

                    this.isSaving = true;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const items = this.getEnteredOrderItems().map((item) => ({
                            item_id: item.itemId || null,
                            nomenclature: String(item.nomenclature || '').trim(),
                            description: String(item.description || '').trim(),
                            quantity: Number.parseInt(item.quantity, 10),
                            unit_cost: Number.parseInt(item.unitCost, 10),
                        }));
                        const response = await fetch(this.saveUrl, {
                            method: this.saveMethod,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify({
                                client_id: this.selectedClientId ? Number(this.selectedClientId) : null,
                                customer_name: String(this.selectedClientQuery || '').trim(),
                                create_client: Boolean(createClient),
                                status: this.orderStatus,
                                items,
                            }),
                        });
                        const payload = await response.json();

                        if (response.status === 422 && payload?.code === 'client_not_found') {
                            this.showCreateClientModal = true;
                            return;
                        }

                        if (!response.ok || !payload?.ok) {
                            throw new Error(payload?.message || (this.isEdit ? 'Не вдалося зберегти зміни.' : 'Не вдалося створити замовлення.'));
                        }

                        if (payload.redirect_url) {
                            this.initialOrderStatus = this.orderStatus;
                            window.location.href = payload.redirect_url;
                        }
                    } catch (error) {
                        this.showWarning(error?.message || (this.isEdit ? 'Не вдалося зберегти зміни.' : 'Не вдалося створити замовлення.'));
                    } finally {
                        this.isSaving = false;
                    }
                },

                normalizeForCompare(value) {
                    return String(value || '').trim().toLocaleLowerCase('uk-UA');
                },

                onClientChanged() {
                    const client = this.clients.find((item) => String(item.id) === String(this.selectedClientId));
                    if (client) {
                        this.selectedClientQuery = client.name || '';
                    }
                },

                onClientInputChanged() {
                    this.clientDropdownActiveIndex = 0;
                    const query = String(this.selectedClientQuery || '').trim();
                    if (query === '') {
                        this.selectedClientId = '';
                        this.syncClientDropdownActiveIndex();
                        this.notifyClientSelectionChanged();
                        return;
                    }

                    const normalizedQuery = this.normalizeForCompare(query);
                    const exact = this.clients.find((client) => this.normalizeForCompare(client.name) === normalizedQuery);
                    if (exact) {
                        this.selectedClientId = String(exact.id);
                        this.onClientChanged();
                    } else {
                        this.selectedClientId = '';
                    }
                    this.syncClientDropdownActiveIndex();
                    this.notifyClientSelectionChanged();
                },

                handleClientInputBlur() {
                    this.applyClientAutoMatch();
                    window.setTimeout(() => {
                        this.showClientDropdown = false;
                        this.clientDropdownActiveIndex = -1;
                    }, 120);
                },

                getFilteredClients() {
                    const query = this.normalizeForCompare(this.selectedClientQuery);
                    if (!query) {
                        return this.clients.slice(0, 50);
                    }

                    return this.clients
                        .filter((client) => this.normalizeForCompare(client.name).includes(query))
                        .slice(0, 50);
                },

                syncClientDropdownActiveIndex() {
                    const filteredClients = this.getFilteredClients();
                    if (filteredClients.length === 0) {
                        this.clientDropdownActiveIndex = -1;
                        return;
                    }

                    if (this.clientDropdownActiveIndex < 0 || this.clientDropdownActiveIndex >= filteredClients.length) {
                        this.clientDropdownActiveIndex = 0;
                    }
                },

                moveClientDropdown(direction) {
                    const filteredClients = this.getFilteredClients();
                    this.showClientDropdown = true;
                    if (filteredClients.length === 0) {
                        this.clientDropdownActiveIndex = -1;
                        return;
                    }

                    if (this.clientDropdownActiveIndex < 0 || this.clientDropdownActiveIndex >= filteredClients.length) {
                        this.clientDropdownActiveIndex = direction > 0 ? 0 : filteredClients.length - 1;
                    } else {
                        this.clientDropdownActiveIndex = (this.clientDropdownActiveIndex + direction + filteredClients.length) % filteredClients.length;
                    }
                },

                selectActiveClient() {
                    const filteredClients = this.getFilteredClients();
                    if (!this.showClientDropdown) {
                        this.showClientDropdown = true;
                        this.syncClientDropdownActiveIndex();
                        return;
                    }

                    if (this.clientDropdownActiveIndex >= 0 && this.clientDropdownActiveIndex < filteredClients.length) {
                        this.selectClient(filteredClients[this.clientDropdownActiveIndex]);
                    }
                },

                selectClient(client) {
                    this.selectedClientQuery = client.name || '';
                    this.selectedClientId = String(client.id);
                    this.showClientDropdown = false;
                    this.clientDropdownActiveIndex = -1;
                    this.notifyClientSelectionChanged();
                },

                notifyClientSelectionChanged() {
                    window.dispatchEvent(new CustomEvent('order-create-client-changed', {
                        detail: {
                            clientId: this.selectedClientId || '',
                            clientName: this.selectedClientQuery || '',
                        },
                    }));
                },

                applyClientAutoMatch() {
                    const query = String(this.selectedClientQuery || '').trim();
                    if (query === '') {
                        this.selectedClientId = '';
                        return;
                    }

                    const normalizedQuery = this.normalizeForCompare(query);
                    const exact = this.clients.find((client) => this.normalizeForCompare(client.name) === normalizedQuery);
                    if (exact) {
                        this.selectClient(exact);
                        return;
                    }

                    const startsWithMatches = this.clients.filter((client) =>
                        this.normalizeForCompare(client.name).startsWith(normalizedQuery)
                    );
                    if (startsWithMatches.length === 1) {
                        this.selectClient(startsWithMatches[0]);
                        return;
                    }

                    const containsMatches = this.clients.filter((client) =>
                        this.normalizeForCompare(client.name).includes(normalizedQuery)
                    );
                    if (containsMatches.length === 1) {
                        this.selectClient(containsMatches[0]);
                        return;
                    }

                    this.selectedClientId = '';
                    this.notifyClientSelectionChanged();
                },
            };
        }
    </script>
</x-app-layout>
