<x-app-layout>
    @section('title', __('Прорахунок замовлення'))
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Прорахунок замовлення') }}
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    {{ __('Повернутись до замовлень') }}
                </a>
                <a href="{{ route('orders.proposals') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    {{ __('Повернутись до заявок') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12">
            <div
                class="space-y-5"
                x-data="orderCalculation({
                    clients: @js($clients),
                    productTypes: @js($productTypes),
                    materials: @js($materials),
                    thicknessByMaterial: @js($thicknessByMaterial),
                    priceOptions: @js($priceOptions),
                })"
            >
                <div class="border-2 border-gray-700 rounded-lg p-4" style="background-color: #FCEEDF;">
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="text-sm font-semibold text-gray-700">Замовник</div>
                        <div class="min-w-[260px] flex-1">
                            <select x-model="selectedClientId" @change="onClientChanged()" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full">
                                <option value="">Оберіть замовника</option>
                                <template x-for="client in clients" :key="client.id">
                                    <option :value="String(client.id)" x-text="client.name"></option>
                                </template>
                            </select>
                        </div>

                        <div class="text-sm font-semibold text-gray-700">Прайс</div>
                        <div class="min-w-[220px]">
                            <select x-model="priceType" :disabled="isPriceTypeLocked" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full disabled:bg-gray-100 disabled:text-gray-500">
                                <template x-for="option in priceOptions" :key="option.value">
                                    <option :value="option.value" x-text="option.label"></option>
                                </template>
                            </select>
                        </div>

                        <div class="text-sm font-semibold text-gray-700">Коефіцієнт терміновості</div>
                        <div class="w-[140px]">
                            <input
                                x-model="urgencyCoefficient"
                                @input="sanitizeDecimalField($event, 'urgencyCoefficient')"
                                type="text"
                                inputmode="decimal"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full"
                            />
                        </div>
                    </div>
                </div>

                <template x-for="(product, productIndex) in products" :key="product.uid">
                    <div class="space-y-4">
                        <div class="border border-gray-300 rounded-lg p-4 space-y-4 bg-white">
                            <div class="flex flex-wrap items-end gap-4">
                                <div class="text-sm font-semibold text-gray-700" x-text="`Тип виробу #${productIndex + 1}`"></div>
                                <div class="min-w-[240px]">
                                    <select x-model="product.productTypeId" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full">
                                        <option value="">Оберіть тип виробу</option>
                                        <template x-for="productType in productTypes" :key="productType.id">
                                            <option :value="String(productType.id)" x-text="productType.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div class="ml-4 text-sm font-semibold text-gray-700">Матеріал</div>
                                <div class="w-[220px]">
                                    <select x-model="product.material" @change="onMaterialChanged(product)" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full">
                                        <option value="">Оберіть матеріал</option>
                                        <template x-for="material in materials" :key="material">
                                            <option :value="material" x-text="material"></option>
                                        </template>
                                    </select>
                                </div>

                                <template x-if="showThicknessForMaterial(product.material)">
                                    <div class="ml-4 flex items-end gap-3 shrink-0">
                                        <div class="text-sm font-semibold text-gray-700 whitespace-nowrap">Товщина матеріалу (мм)</div>
                                        <div class="w-[220px]">
                                            <template x-if="!isCustomerMaterial(product.material)">
                                                <select
                                                    x-model="product.thickness"
                                                    :disabled="isSingleThicknessOption(product.material)"
                                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full disabled:bg-gray-100 disabled:text-gray-500"
                                                >
                                                    <option value="">Оберіть товщину</option>
                                                    <template x-for="thicknessValue in getThicknessOptions(product.material)" :key="thicknessValue">
                                                        <option :value="thicknessValue" x-text="thicknessValue"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="isCustomerMaterial(product.material)">
                                                <div>
                                                    <p x-show="product.manualThicknessError" class="mb-1 text-xs text-red-600" x-text="product.manualThicknessError"></p>
                                                    <input
                                                        x-model="product.manualThickness"
                                                        @input="onManualThicknessInput(product, $event)"
                                                        type="text"
                                                        inputmode="numeric"
                                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full"
                                                    />
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <template x-for="(position, positionIndex) in product.positions" :key="position.uid">
                                <div class="space-y-3 border border-gray-200 rounded-md p-3">
                                    <div class="text-sm font-bold text-gray-800" x-text="`Позиція замовлення #${positionIndex + 1}`"></div>

                                    <div class="flex flex-wrap items-end gap-4">
                                        <div class="text-sm font-semibold text-gray-700">Ширина(м)</div>
                                        <div class="w-[140px]">
                                            <input x-model="position.width" @input="sanitizeDecimalInObject(position, 'width', $event)" type="text" inputmode="decimal" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full" />
                                        </div>

                                        <div class="ml-4 text-sm font-semibold text-gray-700">Висота(м)</div>
                                        <div class="w-[140px]">
                                            <input x-model="position.height" @input="sanitizeDecimalInObject(position, 'height', $event)" type="text" inputmode="decimal" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full" />
                                        </div>

                                        <div class="ml-4 text-sm font-semibold text-gray-700">Кількісь(шт)</div>
                                        <div class="w-[120px]">
                                            <input x-model="position.qty" @input="sanitizeIntegerInObject(position, 'qty', $event)" type="text" inputmode="numeric" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full" />
                                        </div>
                                    </div>

                                    <div x-show="!isUvPrintProduct(product)" class="flex flex-wrap items-end gap-3">
                                        <div class="text-sm font-semibold text-gray-700">Шари друку (шт):</div>
                                        <div class="ml-10 text-sm font-semibold text-gray-700">CMYK</div>
                                        <div class="w-[90px]">
                                            <input x-model="position.cmyk" @input="sanitizeIntegerInObject(position, 'cmyk', $event)" type="text" inputmode="numeric" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full" />
                                        </div>
                                        <div class="text-sm font-semibold text-gray-700">Білий</div>
                                        <div class="w-[90px]">
                                            <input x-model="position.white" @input="sanitizeIntegerInObject(position, 'white', $event)" type="text" inputmode="numeric" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full" />
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div>
                                <button type="button" @click="addPosition(product)" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                                    Додати позицію (лише для однотипного матеріалу)
                                </button>
                            </div>
                        </div>

                        <div class="border border-gray-300 rounded-lg p-4 bg-white">
                            <div class="flex items-center gap-3">
                                <div class="font-semibold text-gray-800" x-text="`Послуги до виробу #${productIndex + 1}`"></div>
                                <div class="inline-flex items-center gap-4">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" :name="`services_enabled_${product.uid}`" value="0" x-model="product.servicesEnabledRaw">
                                        <span>ні</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" :name="`services_enabled_${product.uid}`" value="1" x-model="product.servicesEnabledRaw">
                                        <span>так</span>
                                    </label>
                                </div>
                            </div>

                            <div x-show="product.servicesEnabledRaw === '1'" class="mt-4 space-y-3">
                                <div class="border border-gray-200 rounded-md p-3 flex flex-wrap items-center gap-3">
                                    <div class="font-medium text-gray-700">Ламінування</div>
                                    <select x-model="product.services.lamination" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="Без">Без</option>
                                        <option value="Одностороннє">Одностороннє</option>
                                        <option value="Двостороннє">Двостороннє</option>
                                    </select>
                                </div>

                                <div class="border border-gray-200 rounded-md p-3 flex flex-wrap items-center gap-3">
                                    <div class="font-medium text-gray-700">Порізка</div>
                                    <select x-model="product.services.cutting" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="Фреза">Фреза</option>
                                        <option value="Лазер">Лазер</option>
                                        <option value="Плотер">Плотер</option>
                                    </select>
                                </div>

                                <div class="border border-gray-200 rounded-md p-3 flex flex-wrap items-center gap-3">
                                    <div class="font-medium text-gray-700">Вибірка (складність)</div>
                                    <select x-model="product.services.weeding" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="Низька">Низька</option>
                                        <option value="Середня">Середня</option>
                                        <option value="Висока">Висока</option>
                                    </select>
                                </div>

                                <div class="border border-gray-200 rounded-md p-3 flex flex-wrap items-center gap-3">
                                    <div class="font-medium text-gray-700">Монтажка</div>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="radio" :name="`services_montage_${product.uid}`" value="0" x-model="product.services.montage"><span>ні</span></label>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="radio" :name="`services_montage_${product.uid}`" value="1" x-model="product.services.montage"><span>так</span></label>
                                </div>

                                <div class="border border-gray-200 rounded-md p-3 flex flex-wrap items-center gap-3">
                                    <div class="font-medium text-gray-700">Прикатка</div>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="radio" :name="`services_rolling_${product.uid}`" value="0" x-model="product.services.rolling"><span>ні</span></label>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="radio" :name="`services_rolling_${product.uid}`" value="1" x-model="product.services.rolling"><span>так</span></label>
                                </div>

                                <div class="border border-gray-200 rounded-md p-3 flex flex-wrap items-center gap-3">
                                    <div class="font-medium text-gray-700">Люверси</div>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="radio" :name="`services_eyelets_${product.uid}`" value="0" x-model="product.services.eyelets"><span>ні</span></label>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="radio" :name="`services_eyelets_${product.uid}`" value="1" x-model="product.services.eyelets"><span>так</span></label>

                                    <div class="ml-10 font-medium text-gray-700">Пропайка</div>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="radio" :name="`services_soldering_${product.uid}`" value="0" x-model="product.services.soldering"><span>ні</span></label>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="radio" :name="`services_soldering_${product.uid}`" value="1" x-model="product.services.soldering"><span>так</span></label>
                                </div>

                                <div class="border border-gray-200 rounded-md p-3 flex flex-wrap items-center gap-3">
                                    <div class="font-medium text-gray-700">Дизайн</div>
                                    <div class="ml-10 font-medium text-gray-700">сума(грн)</div>
                                    <div class="w-[120px]">
                                        <input x-model="product.services.designAmount" @input="sanitizeDecimalInObject(product.services, 'designAmount', $event)" type="text" inputmode="decimal" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full" />
                                    </div>
                                </div>

                                <div class="border border-gray-200 rounded-md p-3 flex flex-wrap items-center gap-3">
                                    <div class="font-medium text-gray-700">Пакування</div>
                                    <div class="ml-10 font-medium text-gray-700">Кількість (шт)</div>
                                    <div class="w-[120px]">
                                        <input x-model="product.services.packagingQty" @input="sanitizeIntegerInObject(product.services, 'packagingQty', $event)" type="text" inputmode="numeric" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border border-gray-300 rounded-lg p-4 space-y-3" style="background-color: #FCEEDF;">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="font-bold text-gray-800" x-text="products.length > 1 ? `Вартість виробу #${productIndex + 1}` : 'Вартість загальна (грн)'"></div>
                                <input type="text" value="0.00" disabled class="w-[140px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                <div class="ml-10 font-bold text-gray-800">Собівартість (грн)</div>
                                <input type="text" value="0.00" disabled class="w-[140px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                            </div>
                            <div class="flex items-center justify-between">
                                <button type="button" @click.prevent.stop="noop()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-sm text-gray-900" style="background-color: #27E349;">
                                    Прорахувати
                                </button>
                                <button x-show="products.length === 1" type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-sm text-white" style="background-color: #698DE3;">
                                    Зберегти заявку
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <div>
                    <button
                        type="button"
                        @click="addProduct()"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-semibold text-gray-800"
                        style="background-color: #FCEEDF;"
                    >
                        Додати виріб
                    </button>
                </div>

                <template x-if="products.length > 1">
                    <div class="flex items-end justify-between gap-4">
                        <div class="border-2 border-gray-700 rounded-lg p-4 flex-1 space-y-3" style="background-color: #E3B0A6;">
                            <div class="font-bold text-gray-800">Загальний прорахунок замовлення з усіма виробами та послугами:</div>
                            <div class="flex items-center gap-3">
                                <div class="font-bold text-gray-800">Вартість всього замовлення (грн)</div>
                                <input type="text" value="0.00" disabled class="w-[160px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="font-bold text-gray-800">Собівартість всього замовлення (грн)</div>
                                <input type="text" value="0.00" disabled class="w-[160px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                            </div>
                        </div>
                        <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-sm text-white h-fit" style="background-color: #698DE3;">
                            Зберегти заявку
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        function orderCalculation(config) {
            return {
                clients: config.clients || [],
                productTypes: config.productTypes || [],
                materials: config.materials || [],
                thicknessByMaterial: config.thicknessByMaterial || {},
                priceOptions: config.priceOptions || [],
                selectedClientId: '',
                priceType: 'retail',
                urgencyCoefficient: '1.00',
                isPriceTypeLocked: false,
                products: [],

                init() {
                    this.products = [this.createProduct()];
                    this.onClientChanged();
                },

                createProduct() {
                    return {
                        uid: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
                        productTypeId: '',
                        material: '',
                        thickness: '',
                        manualThickness: '',
                        manualThicknessError: '',
                        positions: [this.createPosition()],
                        servicesEnabledRaw: '0',
                        services: {
                            lamination: 'Без',
                            cutting: 'Фреза',
                            weeding: 'Середня',
                            montage: '0',
                            rolling: '0',
                            eyelets: '0',
                            soldering: '0',
                            designAmount: '0.00',
                            packagingQty: '0',
                        },
                    };
                },

                createPosition() {
                    return {
                        uid: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
                        width: '0',
                        height: '0',
                        qty: '0',
                        cmyk: '0',
                        white: '0',
                    };
                },

                onClientChanged() {
                    const client = this.clients.find((item) => String(item.id) === String(this.selectedClientId));
                    if (client) {
                        this.priceType = client.price_type || 'retail';
                        this.isPriceTypeLocked = true;
                    } else {
                        this.isPriceTypeLocked = false;
                        if (!this.priceOptions.find((option) => option.value === this.priceType)) {
                            this.priceType = 'retail';
                        }
                    }
                },

                getProductTypeName(productTypeId) {
                    const selected = this.productTypes.find((item) => String(item.id) === String(productTypeId));
                    return selected?.name || '';
                },

                isUvPrintProduct(product) {
                    const name = this.getProductTypeName(product.productTypeId).toLowerCase().replace(/\s+/g, ' ').trim();
                    return name.includes('уф друк') || name.includes('уф-друк');
                },

                addPosition(product) {
                    product.positions.push(this.createPosition());
                },

                addProduct() {
                    this.products.push(this.createProduct());
                },

                normalizeMaterial(value) {
                    return (value || '').trim().toLowerCase();
                },

                isCustomerMaterial(material) {
                    return this.normalizeMaterial(material) === 'матеріал замовника';
                },

                showThicknessForMaterial(material) {
                    const normalized = this.normalizeMaterial(material);
                    const isBanner = normalized.includes('банер');
                    const isFilm = normalized.includes('плівк');
                    return !isBanner && !isFilm;
                },

                noop() {
                    // Заглушка: кнопка "Прорахувати" поки без функціоналу і не повинна скидати стан форми.
                },

                getThicknessOptions(material) {
                    return this.thicknessByMaterial[material] || [];
                },

                isSingleThicknessOption(material) {
                    return this.getThicknessOptions(material).length === 1;
                },

                onMaterialChanged(product) {
                    product.thickness = '';
                    product.manualThickness = '';
                    product.manualThicknessError = '';

                    const options = this.getThicknessOptions(product.material);
                    if (options.length === 1) {
                        product.thickness = options[0];
                    }
                },

                onManualThicknessInput(product, event) {
                    const value = String(event.target.value || '');
                    product.manualThickness = value;

                    if (value === '') {
                        product.manualThicknessError = '';
                        return;
                    }

                    product.manualThicknessError = /^\d+$/.test(value) ? '' : 'Дозволені лише цілі числа.';
                },

                sanitizeDecimalField(event, fieldName) {
                    this[fieldName] = this.sanitizeDecimalValue(event.target.value);
                    event.target.value = this[fieldName];
                },

                sanitizeDecimalInObject(target, fieldName, event) {
                    target[fieldName] = this.sanitizeDecimalValue(event.target.value);
                    event.target.value = target[fieldName];
                },

                sanitizeIntegerInObject(target, fieldName, event) {
                    target[fieldName] = this.sanitizeIntegerValue(event.target.value);
                    event.target.value = target[fieldName];
                },

                sanitizeDecimalValue(raw) {
                    let value = String(raw || '').replace(',', '.').replace(/[^0-9.]/g, '');
                    const firstDot = value.indexOf('.');
                    if (firstDot !== -1) {
                        value = value.slice(0, firstDot + 1) + value.slice(firstDot + 1).replace(/\./g, '');
                        const decimals = value.slice(firstDot + 1);
                        if (decimals.length > 2) {
                            value = value.slice(0, firstDot + 1) + decimals.slice(0, 2);
                        }
                    }
                    if (value.startsWith('.')) {
                        value = '0' + value;
                    }
                    return value;
                },

                sanitizeIntegerValue(raw) {
                    return String(raw || '').replace(/\D/g, '');
                },
            };
        }
    </script>
</x-app-layout>
