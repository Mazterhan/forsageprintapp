<x-app-layout>
    @section('title', $title)
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12 space-y-6">
            @if ($errors->any())
                <div class="text-sm text-red-700 bg-red-100 px-4 py-2 rounded">
                    {{ __('Перевірте коректність заповнення полів.') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form id="price-item-form" method="POST" action="{{ route('price.store') }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="md:col-span-8">
                                <x-input-label for="name" :value="__('Назва')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>
                            <div class="md:col-span-4">
                                <x-input-label for="model_type" :value="__('Модель позиції')" />
                                <select id="model_type" name="model_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    <option value="" disabled @selected(old('model_type') === null) hidden>{{ __('Оберіть модель') }}</option>
                                    <option value="Матеріал" @selected(old('model_type') === 'Матеріал')>{{ __('Матеріал') }}</option>
                                    <option value="Послуга" @selected(old('model_type') === 'Послуга')>{{ __('Послуга') }}</option>
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('model_type')" />
                            </div>
                        </div>

                        <div id="material-block" class="space-y-4 hidden">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <div class="md:col-span-4">
                                    <x-input-label for="category" :value="__('Категорія')" />
                                    <select id="category" name="category" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="">{{ __('Оберіть категорію') }}</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->name }}" data-material-type="{{ $category->material_type }}" @selected(old('category') === $category->name)>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('category')" />
                                </div>
                                <div id="thickness-wrap" class="md:col-span-4 hidden">
                                    <x-input-label for="thickness_mm" :value="__('Товщина (мм)')" />
                                    <x-text-input id="thickness_mm" name="thickness_mm" type="text" class="mt-1 block w-full" value="{{ old('thickness_mm') }}" />
                                    <x-input-error class="mt-2" :messages="$errors->get('thickness_mm')" />
                                </div>
                                <div class="md:col-span-4">
                                    <x-input-label for="material_type_display" :value="__('Тип')" />
                                    <x-text-input id="material_type_display" type="text" class="mt-1 block w-full bg-gray-100" value="" disabled />
                                </div>
                            </div>
                        </div>

                        <div id="price-row" class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="md:col-span-3">
                                <x-input-label for="purchase_price" :value="__('Закупівельна вартість')" />
                                <x-text-input id="purchase_price" name="purchase_price" type="text" class="mt-1 block w-full" value="{{ old('purchase_price') }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('purchase_price')" />
                            </div>
                            <div class="md:col-span-3">
                                <x-input-label for="service_price" :value="__('Розрахункова вартість')" />
                                <x-text-input id="service_price" name="service_price" type="text" class="mt-1 block w-full" value="{{ old('service_price') }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('service_price')" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="markup_percent" :value="__('Націнка %')" />
                                <x-text-input id="markup_percent" type="text" class="mt-1 block w-full" value="{{ old('markup_percent') }}" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="measurement_unit" :value="__('Вимірювання')" />
                                <select id="measurement_unit" name="measurement_unit" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    <option value="" disabled @selected(old('measurement_unit') === null) hidden></option>
                                    <option value="м2" @selected(old('measurement_unit') === 'м2')>м2</option>
                                    <option value="шт." @selected(old('measurement_unit') === 'шт.')>шт.</option>
                                    <option value="м.п." @selected(old('measurement_unit') === 'м.п.')>м.п.</option>
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('measurement_unit')" />
                            </div>
                            <div id="service-toggle-wrap" class="md:col-span-2">
                                <x-input-label :value="__('Для матеріалу замовника')" />
                                <input id="for_customer_material" type="hidden" name="for_customer_material" value="{{ old('for_customer_material', 0) ? 1 : 0 }}">
                                <div class="mt-2 flex items-center gap-2">
                                    <input
                                        id="for_customer_material_toggle"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        @checked(old('for_customer_material', 0))
                                    >
                                    <label for="for_customer_material_toggle" class="text-sm text-gray-700">
                                        {{ __('Так') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="w-full min-w-0">
                            <x-input-label for="comment" :value="__('Коментар')" />
                            <textarea
                                id="comment"
                                name="comment"
                                rows="3"
                                class="mt-1 block w-full min-w-0 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm resize-y"
                            >{{ old('comment') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('comment')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button id="create-price-item-submit">{{ __('Додати позицію') }}</x-primary-button>
                            <a href="{{ route('price.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-200">
                                {{ __('Відхилити') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('price-item-form');
            const modelType = document.getElementById('model_type');
            const materialBlock = document.getElementById('material-block');
            const category = document.getElementById('category');
            const materialTypeDisplay = document.getElementById('material_type_display');
            const thicknessWrap = document.getElementById('thickness-wrap');
            const thicknessInput = document.getElementById('thickness_mm');
            const serviceToggleWrap = document.getElementById('service-toggle-wrap');
            const forCustomerInput = document.getElementById('for_customer_material');
            const forCustomerToggle = document.getElementById('for_customer_material_toggle');
            const submitButton = document.getElementById('create-price-item-submit');
            const servicePriceInput = document.getElementById('service_price');
            const purchasePriceInput = document.getElementById('purchase_price');
            const markupPercentInput = document.getElementById('markup_percent');
            let isDirty = false;
            let isSyncingMarkup = false;
            let isSubmitting = false;

            const syncForCustomerValue = () => {
                if (!forCustomerToggle || !forCustomerInput) {
                    return;
                }
                forCustomerInput.value = forCustomerToggle.checked ? '1' : '0';
            };

            const updateMaterialType = () => {
                const selected = category?.selectedOptions?.[0];
                const currentMaterialType = selected?.dataset?.materialType || '';
                materialTypeDisplay.value = currentMaterialType;

                const isSheet = currentMaterialType === 'Листовий' && modelType?.value === 'Матеріал';
                thicknessWrap?.classList.toggle('hidden', !isSheet);
                if (thicknessInput) {
                    thicknessInput.required = isSheet;
                    if (!isSheet) {
                        thicknessInput.value = '';
                    }
                }
            };

            const parseNumber = (value) => {
                if (value === null || value === undefined) {
                    return NaN;
                }
                const normalized = String(value).replace(',', '.').trim();
                if (normalized === '') {
                    return NaN;
                }
                return Number.parseFloat(normalized);
            };

            const round2 = (value) => Math.round(value * 100) / 100;
            const trimDecimalDisplay = (value) => {
                if (value === null || value === undefined) return '';
                const normalized = String(value).trim();
                if (normalized === '') return '';
                return normalized.replace(/(\.\d*?[1-9])0+$/,'$1').replace(/\.0+$/,'');
            };
            const sanitizeThicknessValue = (raw) => {
                let value = String(raw || '').replace(',', '.').replace(/[^0-9.]/g, '');
                const firstDot = value.indexOf('.');
                if (firstDot !== -1) {
                    value = value.slice(0, firstDot + 1) + value.slice(firstDot + 1).replace(/\./g, '');
                    const decimals = value.slice(firstDot + 1);
                    if (decimals.length > 1) {
                        value = value.slice(0, firstDot + 1) + decimals.slice(0, 1);
                    }
                }
                if (value.startsWith('.')) {
                    value = '0' + value;
                }
                if (value.includes('.')) {
                    const [intPart, decPart] = value.split('.', 2);
                    const normalizedInt = intPart.replace(/^0+(?=\d)/, '') || '0';
                    value = normalizedInt + '.' + (decPart ?? '');
                } else {
                    value = value.replace(/^0+(?=\d)/, '');
                }
                return trimDecimalDisplay(value);
            };

            const syncMarkupFromPrices = () => {
                if (isSyncingMarkup) {
                    return;
                }
                const purchase = parseNumber(purchasePriceInput?.value);
                const service = parseNumber(servicePriceInput?.value);
                if (!Number.isFinite(purchase) || purchase <= 0 || !Number.isFinite(service)) {
                    markupPercentInput.value = '';
                    return;
                }
                const markup = ((service - purchase) / purchase) * 100;
                markupPercentInput.value = Number.isFinite(markup) ? round2(markup).toFixed(2) : '';
            };

            const syncServiceFromMarkup = () => {
                if (isSyncingMarkup) {
                    return;
                }
                const purchase = parseNumber(purchasePriceInput?.value);
                const markup = parseNumber(markupPercentInput?.value);
                if (!Number.isFinite(purchase) || purchase <= 0 || !Number.isFinite(markup)) {
                    return;
                }
                isSyncingMarkup = true;
                const service = purchase * (1 + (markup / 100));
                servicePriceInput.value = round2(service).toFixed(2);
                isSyncingMarkup = false;
            };

            const updateMode = () => {
                const type = modelType.value;
                const isMaterial = type === 'Матеріал';
                const isService = type === 'Послуга';

                materialBlock.classList.toggle('hidden', !isMaterial);
                serviceToggleWrap.classList.toggle('hidden', !isService);
                if (!isService) {
                    if (forCustomerToggle) {
                        forCustomerToggle.checked = false;
                    }
                    forCustomerInput.value = '0';
                }
                category.required = isMaterial;
                document.getElementById('service_price').required = isMaterial;
                updateMaterialType();
            };

            const handleToggleChange = () => {
                syncForCustomerValue();
                isDirty = true;
            };

            forCustomerToggle?.addEventListener('change', handleToggleChange);

            modelType?.addEventListener('change', () => {
                updateMode();
                isDirty = true;
            });

            category?.addEventListener('change', () => {
                updateMaterialType();
                isDirty = true;
            });

            thicknessInput?.addEventListener('input', (event) => {
                const sanitized = sanitizeThicknessValue(event.target.value);
                thicknessInput.value = sanitized;
                isDirty = true;
            });

            thicknessInput?.addEventListener('blur', () => {
                thicknessInput.value = sanitizeThicknessValue(thicknessInput.value);
            });

            form?.addEventListener('input', () => {
                isDirty = true;
            });

            servicePriceInput?.addEventListener('input', () => {
                syncMarkupFromPrices();
            });
            purchasePriceInput?.addEventListener('input', () => {
                syncMarkupFromPrices();
                syncServiceFromMarkup();
            });
            markupPercentInput?.addEventListener('input', () => {
                syncServiceFromMarkup();
            });

            form?.addEventListener('submit', () => {
                if (isSubmitting) {
                    return;
                }
                isSubmitting = true;
                syncForCustomerValue();
                isDirty = false;
                if (submitButton) {
                    submitButton.setAttribute('disabled', 'disabled');
                }
            });

            window.addEventListener('beforeunload', (event) => {
                if (!isDirty) {
                    return;
                }
                event.preventDefault();
                event.returnValue = '';
            });

            syncForCustomerValue();
            updateMode();
            syncMarkupFromPrices();
        })();
    </script>
</x-app-layout>
