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
                    materialTypeByMaterial: @js($materialTypeByMaterial),
                    materialCategoryByMaterial: @js($materialCategoryByMaterial),
                    materialCategoriesByMaterial: @js($materialCategoriesByMaterial),
                    typeCategoryMatrix: @js($typeCategoryMatrix),
                    priceOptions: @js($priceOptions),
                })"
            >
                <div class="sticky top-0 z-30 border-2 border-gray-700 rounded-lg p-4 shadow-sm" style="background-color: #FCEEDF;">
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
                                @input="sanitizeUrgencyInput($event)"
                                @blur="normalizeUrgencyOnBlur($event)"
                                type="text"
                                inputmode="decimal"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full"
                            />
                        </div>
                        <div class="ml-auto">
                            <button
                                type="button"
                                @click="addProduct()"
                                :disabled="!canShowAddProductButton()"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-semibold text-gray-800"
                                :style="`background-color: ${canShowAddProductButton() ? '#0FA10B' : '#F3F4F6'};`"
                            >
                                Додати виріб
                            </button>
                        </div>
                    </div>
                </div>

                <template x-for="(product, productIndex) in products" :key="product.uid">
                    <div class="space-y-4">
                        <div x-show="product.isExpanded" class="border border-gray-300 rounded-lg p-4 space-y-4 bg-white">
                            <div class="flex flex-wrap items-end gap-4">
                                <div class="text-sm font-semibold text-gray-700" x-text="`Тип виробу #${displayProductNumber(productIndex)}`"></div>
                                <div class="min-w-[240px]">
                                    <select x-model="product.productTypeId" @change="onProductTypeChanged(product)" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full">
                                        <option value="">Оберіть тип виробу</option>
                                        <template x-for="productType in productTypes" :key="productType.id">
                                            <option :value="String(productType.id)" x-text="productType.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div class="ml-4 text-sm font-semibold text-gray-700">Матеріал</div>
                                <div class="w-[220px]">
                                    <select
                                        x-model="product.material"
                                        @change="onMaterialChanged(product)"
                                        :disabled="!product.productTypeId"
                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full disabled:bg-gray-100 disabled:text-gray-500"
                                    >
                                        <option value="">Оберіть матеріал</option>
                                        <template x-for="material in getAllowedMaterials(product)" :key="material">
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
                                                    :disabled="!product.productTypeId || isSingleThicknessOption(product.material)"
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
                                                        :disabled="!product.productTypeId"
                                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full disabled:bg-gray-100 disabled:text-gray-500"
                                                    />
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <template x-for="(position, positionIndex) in product.positions" :key="position.uid">
                                <div class="space-y-3 border border-gray-200 rounded-md p-3">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="text-sm font-bold text-gray-800" x-text="`Позиція замовлення #${positionIndex + 1}`"></div>
                                        <button
                                            x-show="product.positions.length > 1"
                                            type="button"
                                            @click="removePosition(product, positionIndex)"
                                            class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-xs font-semibold text-gray-800"
                                            style="background-color: #EFE4B0;"
                                        >
                                            Видалити позицію замовлення
                                        </button>
                                    </div>

                                    <div class="flex flex-wrap items-end gap-4">
                                        <div class="text-sm font-semibold text-gray-700">Ширина(м)</div>
                                        <div class="w-[140px]">
                                            <input :disabled="!product.material" x-model="position.width" @focus="clearDefaultZero($event, 'decimal')" @blur="restoreDefaultOnBlur(position, 'width', '0', $event)" @input="sanitizeDecimalInObject(position, 'width', $event)" type="text" inputmode="decimal" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full disabled:bg-gray-100 disabled:text-gray-500" />
                                        </div>

                                        <div class="ml-4 text-sm font-semibold text-gray-700">Висота(м)</div>
                                        <div class="w-[140px]">
                                            <input :disabled="!product.material" x-model="position.height" @focus="clearDefaultZero($event, 'decimal')" @blur="restoreDefaultOnBlur(position, 'height', '0', $event)" @input="sanitizeDecimalInObject(position, 'height', $event)" type="text" inputmode="decimal" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full disabled:bg-gray-100 disabled:text-gray-500" />
                                        </div>

                                        <div class="ml-4 text-sm font-semibold text-gray-700">Кількісь(шт)</div>
                                        <div class="w-[120px]">
                                            <input :disabled="!product.material" x-model="position.qty" @focus="clearDefaultZero($event, 'integer')" @blur="restoreDefaultOnBlur(position, 'qty', '0', $event)" @input="sanitizeIntegerInObject(position, 'qty', $event)" type="text" inputmode="numeric" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full disabled:bg-gray-100 disabled:text-gray-500" />
                                        </div>
                                        <div class="ml-auto mr-1 flex items-center gap-2 shrink-0">
                                            <input type="text" value="0.00" disabled class="w-[110px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                            <span class="text-sm text-gray-700">грн</span>
                                        </div>
                                    </div>

                                    <div x-show="isUvPrintProduct(product)" class="flex flex-wrap items-end gap-3">
                                        <div class="text-sm font-semibold text-gray-700">Шари друку (шт):</div>
                                        <div class="ml-10 text-sm font-semibold text-gray-700">CMYK</div>
                                        <div class="w-[90px]">
                                            <input x-model="position.cmyk" @focus="clearDefaultZero($event, 'integer')" @blur="restoreDefaultOnBlur(position, 'cmyk', '0', $event)" @input="sanitizeIntegerInObject(position, 'cmyk', $event)" type="text" inputmode="numeric" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full" />
                                        </div>
                                        <div class="text-sm font-semibold text-gray-700">Білий</div>
                                        <div class="w-[90px]">
                                            <input x-model="position.white" @focus="clearDefaultZero($event, 'integer')" @blur="restoreDefaultOnBlur(position, 'white', '0', $event)" @input="sanitizeIntegerInObject(position, 'white', $event)" type="text" inputmode="numeric" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full" />
                                        </div>
                                        <div x-show="!isUvLayersValid(position)" class="text-xs font-semibold text-red-600">
                                            Для УФ Друк потрібно, щоб CMYK або Білий були більше 0.
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div>
                                <button type="button" @click="addPosition(product)" :disabled="!product.material" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                                    Додати позицію (лише для однотипного матеріалу)
                                </button>
                            </div>
                        </div>

                        <div x-show="product.isExpanded && product.positions.length === 1" class="border border-gray-300 rounded-lg p-4 bg-white">
                            <div class="flex items-center gap-3">
                                <div class="font-semibold text-gray-800" x-text="`Послуги до виробу #${displayProductNumber(productIndex)}`"></div>
                                <div class="inline-flex items-center gap-4">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input
                                            type="radio"
                                            :name="`services_enabled_${product.uid}`"
                                            value="0"
                                            x-model="product.servicesEnabledRaw"
                                            :disabled="!product.material"
                                        >
                                        <span>ні</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input
                                            type="radio"
                                            :name="`services_enabled_${product.uid}`"
                                            value="1"
                                            x-model="product.servicesEnabledRaw"
                                            :disabled="!product.material"
                                        >
                                        <span>так</span>
                                    </label>
                                </div>
                            </div>

                            <div x-show="product.material && product.servicesEnabledRaw === '1'" class="mt-4 space-y-3">
                                <div x-show="isServiceBlockVisible(product, 'lamination')" class="border border-gray-200 rounded-md p-3 space-y-2">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="font-medium text-gray-700">Ламінування</div>
                                        <select x-model="product.services.lamination" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                            <option value="Без">Без</option>
                                            <option value="Одностороннє">Одностороннє</option>
                                            <option value="Двостороннє">Двостороннє</option>
                                        </select>
                                    </div>
                                    <div x-show="product.services.lamination !== 'Без'" class="flex flex-wrap items-end gap-3">
                                        <div class="text-sm text-gray-700">Ширина(м)</div>
                                        <input type="text" :value="getFirstPositionValue(product, 'width', '0')" disabled class="w-[90px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                        <div class="text-sm text-gray-700">Висота(м)</div>
                                        <input type="text" :value="getFirstPositionValue(product, 'height', '0')" disabled class="w-[90px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                        <div class="text-sm text-gray-700">Кількість(шт)</div>
                                        <input type="text" :value="getFirstPositionValue(product, 'qty', '0')" disabled class="w-[90px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                        <div class="ml-auto mr-1 flex items-center gap-2 shrink-0">
                                            <input type="text" value="0.00" disabled class="w-[110px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                            <span class="text-sm text-gray-700">грн</span>
                                        </div>
                                    </div>
                                </div>

                                <div x-show="isServiceBlockVisible(product, 'cutting')" class="border border-gray-200 rounded-md p-3 space-y-2">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="font-medium text-gray-700">Порізка</div>
                                        <select x-model="product.services.cutting" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                            <option value="Без порізки">Без порізки</option>
                                            <template x-for="option in getCuttingOptions(product)" :key="option">
                                                <option :value="option" x-text="option"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div x-show="product.services.cutting !== 'Без порізки'" class="flex flex-wrap items-end gap-3">
                                        <div class="text-sm text-gray-700">Довжина порізки(м.п.)</div>
                                        <input
                                            x-model="product.services.cuttingLength"
                                            @focus="clearDefaultZero($event, 'integer')"
                                            @blur="restoreDefaultOnBlur(product.services, 'cuttingLength', '0', $event)"
                                            @input="sanitizeIntegerInObject(product.services, 'cuttingLength', $event)"
                                            type="text"
                                            inputmode="numeric"
                                            class="w-[110px] border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        />
                                        <div class="ml-auto mr-1 flex items-center gap-2 shrink-0">
                                            <input type="text" value="0.00" disabled class="w-[110px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                            <span class="text-sm text-gray-700">грн</span>
                                        </div>
                                    </div>
                                </div>

                                <div x-show="isServiceBlockVisible(product, 'weeding')" class="border border-gray-200 rounded-md p-3 space-y-2">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="font-medium text-gray-700">Вибірка (складність)</div>
                                        <div class="ml-[40px]"></div>
                                        <input
                                            x-model="product.services.weedingPrice"
                                            @focus="clearDefaultZero($event, 'decimal')"
                                            @blur="restoreDefaultOnBlur(product.services, 'weedingPrice', '0.00', $event)"
                                            @input="sanitizeDecimalInObject(product.services, 'weedingPrice', $event)"
                                            type="text"
                                            inputmode="decimal"
                                            class="w-[110px] border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        />
                                        <div class="text-sm text-gray-700">ціна (грн/м.кв.)</div>
                                    </div>
                                    <div class="flex flex-wrap items-end gap-3">
                                        <div class="text-sm text-gray-700">Ширина(м)</div>
                                        <input type="text" :value="getFirstPositionValue(product, 'width', '0')" disabled class="w-[90px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                        <div class="text-sm text-gray-700">Висота(м)</div>
                                        <input type="text" :value="getFirstPositionValue(product, 'height', '0')" disabled class="w-[90px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                        <div class="text-sm text-gray-700">Кількість(шт)</div>
                                        <input type="text" :value="getFirstPositionValue(product, 'qty', '0')" disabled class="w-[90px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                        <div class="ml-auto mr-1 flex items-center gap-2 shrink-0">
                                            <input type="text" value="0.00" disabled class="w-[110px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                            <span class="text-sm text-gray-700">грн</span>
                                        </div>
                                    </div>
                                </div>

                                <div x-show="isServiceBlockVisible(product, 'montage')" class="border border-gray-200 rounded-md p-3 space-y-2">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="font-medium text-gray-700">Монтажка</div>
                                    </div>
                                    <div class="flex flex-wrap items-end gap-3">
                                        <div class="text-sm text-gray-700">Ширина(м)</div>
                                        <input type="text" :value="getFirstPositionValue(product, 'width', '0')" disabled class="w-[90px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                        <div class="text-sm text-gray-700">Висота(м)</div>
                                        <input type="text" :value="getFirstPositionValue(product, 'height', '0')" disabled class="w-[90px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                        <div class="text-sm text-gray-700">Кількість(шт)</div>
                                        <input type="text" :value="getFirstPositionValue(product, 'qty', '0')" disabled class="w-[90px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                        <div class="ml-auto mr-1 flex items-center gap-2 shrink-0">
                                            <input type="text" value="0.00" disabled class="w-[110px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                            <span class="text-sm text-gray-700">грн</span>
                                        </div>
                                    </div>
                                </div>

                                <div x-show="isServiceBlockVisible(product, 'rolling')" class="border border-gray-200 rounded-md p-3 flex flex-wrap items-center gap-3">
                                    <div class="font-medium text-gray-700">Прикатка</div>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="radio" :name="`services_rolling_${product.uid}`" value="0" x-model="product.services.rolling"><span>ні</span></label>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="radio" :name="`services_rolling_${product.uid}`" value="1" x-model="product.services.rolling"><span>так</span></label>
                                </div>

                                <div x-show="isServiceBlockVisible(product, 'eyelets_soldering')" class="border border-gray-200 rounded-md p-3 space-y-2">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="font-medium text-gray-700">Люверси</div>
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="radio" :name="`services_eyelets_mode_${product.uid}`" value="Шаг" x-model="product.services.eyeletsMode"><span>Шаг</span></label>
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="radio" :name="`services_eyelets_mode_${product.uid}`" value="Штуки" x-model="product.services.eyeletsMode"><span>Штуки</span></label>
                                        <input
                                            x-model="product.services.eyeletsValue"
                                            @focus="clearDefaultZero($event, 'integer')"
                                            @blur="restoreDefaultOnBlur(product.services, 'eyeletsValue', '0', $event)"
                                            @input="sanitizeIntegerInObject(product.services, 'eyeletsValue', $event)"
                                            type="text"
                                            inputmode="numeric"
                                            class="w-[90px] border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        />
                                        <div class="text-sm text-gray-700" x-text="product.services.eyeletsMode === 'Штуки' ? '(штук)' : '(см)'"></div>
                                        <div class="ml-auto mr-1 flex items-center gap-2 shrink-0">
                                            <input type="text" value="0.00" disabled class="w-[110px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                            <span class="text-sm text-gray-700">грн</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-end gap-3">
                                        <div class="font-medium text-gray-700">Пропайка</div>
                                        <div class="text-sm text-gray-700">Довжина порізки (м.п.)</div>
                                        <input
                                            x-model="product.services.solderingLength"
                                            @focus="clearDefaultZero($event, 'integer')"
                                            @blur="restoreDefaultOnBlur(product.services, 'solderingLength', '0', $event)"
                                            @input="sanitizeIntegerInObject(product.services, 'solderingLength', $event)"
                                            type="text"
                                            inputmode="numeric"
                                            class="w-[110px] border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        />
                                        <div class="ml-auto mr-1 flex items-center gap-2 shrink-0">
                                            <input type="text" value="0.00" disabled class="w-[110px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                            <span class="text-sm text-gray-700">грн</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="border border-gray-200 rounded-md p-3 flex flex-wrap items-center gap-3">
                                    <div class="font-medium text-gray-700">Дизайн</div>
                                    <div class="ml-10 font-medium text-gray-700">сума(грн)</div>
                                    <div class="w-[120px]">
                                        <input x-model="product.services.designAmount" @focus="clearDefaultZero($event, 'decimal')" @blur="restoreDefaultOnBlur(product.services, 'designAmount', '0.00', $event)" @input="sanitizeDecimalInObject(product.services, 'designAmount', $event)" type="text" inputmode="decimal" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full" />
                                    </div>
                                    <div class="ml-auto mr-1 flex items-center gap-2 shrink-0">
                                        <input type="text" value="0.00" disabled class="w-[110px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                        <span class="text-sm text-gray-700">грн</span>
                                    </div>
                                </div>

                                <div class="border border-gray-200 rounded-md p-3 flex flex-wrap items-center gap-3">
                                    <div class="font-medium text-gray-700">Пакування</div>
                                    <div class="ml-10 font-medium text-gray-700">Кількість (шт)</div>
                                    <div class="w-[120px]">
                                        <input x-model="product.services.packagingQty" @focus="clearDefaultZero($event, 'integer')" @blur="restoreDefaultOnBlur(product.services, 'packagingQty', '0', $event)" @input="sanitizeIntegerInObject(product.services, 'packagingQty', $event)" type="text" inputmode="numeric" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full" />
                                    </div>
                                    <div class="ml-auto mr-1 flex items-center gap-2 shrink-0">
                                        <input type="text" value="0.00" disabled class="w-[110px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                        <span class="text-sm text-gray-700">грн</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border border-gray-300 rounded-lg p-4 space-y-3" style="background-color: #FCEEDF;">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="font-bold text-gray-800" x-text="products.length > 1 ? `Вартість виробу #${displayProductNumber(productIndex)}` : 'Вартість загальна (грн)'"></div>
                                <input type="text" value="0.00" disabled class="w-[140px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                <div class="ml-10 font-bold text-gray-800">Собівартість (грн)</div>
                                <input type="text" value="0.00" disabled class="w-[140px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
                                <div x-show="!product.isExpanded && products.length > 1" class="ml-auto flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="product.isExpanded = true"
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-sm text-white"
                                        style="background-color: #698DE3;"
                                        x-text="`Розгорнути деталі виробу #${displayProductNumber(productIndex)}`"
                                    ></button>
                                    <button
                                        x-show="products.length > 1 && productIndex !== 0"
                                        type="button"
                                        @click="removeProduct(productIndex)"
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-sm text-white"
                                        style="background-color: #EF795A;"
                                        x-text="`Видалити тип виробу #${displayProductNumber(productIndex)}`"
                                    ></button>
                                </div>
                            </div>
                            <div x-show="product.isExpanded" class="flex items-center justify-between">
                                <button type="button" @click.prevent.stop="noop()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-sm text-gray-900" style="background-color: #27E349;">
                                    Прорахувати
                                </button>
                                <div class="ml-auto flex items-center gap-2">
                                    <button
                                        x-show="products.length > 1"
                                        type="button"
                                        @click="product.isExpanded = false"
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-sm text-white"
                                        style="background-color: #698DE3;"
                                        x-text="`Згорнути деталі виробу #${displayProductNumber(productIndex)}`"
                                    ></button>
                                    <button
                                        x-show="products.length > 1 && productIndex !== 0"
                                        type="button"
                                        @click="removeProduct(productIndex)"
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-sm text-white"
                                        style="background-color: #EF795A;"
                                        x-text="`Видалити тип виробу #${displayProductNumber(productIndex)}`"
                                    ></button>
                                    <button x-show="products.length === 1" type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-sm text-white" style="background-color: #698DE3;">
                                        Зберегти заявку
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

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
                materialTypeByMaterial: config.materialTypeByMaterial || {},
                materialCategoryByMaterial: config.materialCategoryByMaterial || {},
                materialCategoriesByMaterial: config.materialCategoriesByMaterial || {},
                typeCategoryMatrix: config.typeCategoryMatrix || {},
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
                        isExpanded: true,
                        productTypeId: '',
                        material: '',
                        thickness: '',
                        manualThickness: '',
                        manualThicknessError: '',
                        positions: [this.createPosition()],
                        servicesEnabledRaw: '0',
                        services: {
                            lamination: 'Без',
                            cutting: 'Без порізки',
                            cuttingLength: '0',
                            weedingPrice: '0.00',
                            rolling: '0',
                            eyeletsMode: 'Шаг',
                            eyeletsValue: '0',
                            solderingLength: '0',
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

                normalizeForCompare(value) {
                    return (value || '').toString().trim().toLowerCase();
                },

                isProductType(productTypeId, expectedName) {
                    return this.normalizeForCompare(this.getProductTypeName(productTypeId)) === this.normalizeForCompare(expectedName);
                },

                isCustomSheetMaterial(material) {
                    return this.normalizeMaterial(material) === 'матеріал замовника листовий';
                },

                isCustomRollMaterial(material) {
                    return this.normalizeMaterial(material) === 'матеріал замовника рулонний';
                },

                isAllowedCustomSheetMaterial(productTypeId) {
                    return !this.isProductType(productTypeId, 'Сольвентний друк');
                },

                isAllowedCustomRollMaterial(productTypeId) {
                    return (
                        this.isProductType(productTypeId, 'УФ друк') ||
                        this.isProductType(productTypeId, 'Сольвентний друк') ||
                        this.isProductType(productTypeId, 'Чиста порізка')
                    );
                },

                isMaterialAllowedForProductType(product, material) {
                    if (!product.productTypeId) {
                        return true;
                    }

                    if (this.isCustomSheetMaterial(material)) {
                        return this.isAllowedCustomSheetMaterial(product.productTypeId);
                    }

                    if (this.isCustomRollMaterial(material)) {
                        return this.isAllowedCustomRollMaterial(product.productTypeId);
                    }

                    const categories = this.materialCategoriesByMaterial[material] || [];
                    if (!Array.isArray(categories) || categories.length === 0) {
                        return false;
                    }

                    const matrixForType = this.typeCategoryMatrix[String(product.productTypeId)] || {};
                    return categories.some((categoryName) => matrixForType[categoryName] === true);
                },

                getAllowedMaterials(product) {
                    return this.materials.filter((material) => this.isMaterialAllowedForProductType(product, material));
                },

                isUvPrintProduct(product) {
                    const name = this.getProductTypeName(product.productTypeId).toLowerCase().replace(/\s+/g, ' ').trim();
                    return name.includes('уф друк') || name.includes('уф-друк');
                },

                isUvLayersValid(position) {
                    const cmyk = parseInt(position.cmyk || '0', 10);
                    const white = parseInt(position.white || '0', 10);
                    return cmyk > 0 || white > 0;
                },

                addPosition(product) {
                    this.ensureCuttingValue(product);
                    product.positions.push(this.createPosition());
                    if (product.positions.length > 1) {
                        product.servicesEnabledRaw = '0';
                    }
                },

                removePosition(product, positionIndex) {
                    if (product.positions.length <= 1) {
                        return;
                    }
                    product.positions.splice(positionIndex, 1);
                    if (product.positions.length > 1) {
                        product.servicesEnabledRaw = '0';
                    }
                },

                addProduct() {
                    const product = this.createProduct();
                    this.ensureCuttingValue(product);
                    this.products.forEach((item) => {
                        item.isExpanded = false;
                    });
                    this.products.unshift(product);
                },

                removeProduct(productIndex) {
                    if (this.products.length <= 1) {
                        return;
                    }
                    this.products.splice(productIndex, 1);
                },

                canShowAddProductButton() {
                    if (!Array.isArray(this.products) || this.products.length === 0) {
                        return false;
                    }

                    const currentProduct = this.products[0];
                    return Boolean(currentProduct?.productTypeId) && Boolean(currentProduct?.material);
                },

                displayProductNumber(productIndex) {
                    return this.products.length - productIndex;
                },

                getFirstPositionValue(product, fieldName, defaultValue = '0') {
                    const firstPosition = product?.positions?.[0];
                    const raw = firstPosition ? String(firstPosition[fieldName] ?? '').trim() : '';
                    return raw === '' ? defaultValue : raw;
                },

                normalizeMaterial(value) {
                    return (value || '').trim().toLowerCase();
                },

                isCustomerMaterial(material) {
                    return this.normalizeMaterial(material) === 'матеріал замовника листовий';
                },

                isCustomerRollMaterial(material) {
                    return this.normalizeMaterial(material) === 'матеріал замовника рулонний';
                },

                getMaterialType(material) {
                    return this.materialTypeByMaterial[material] || '';
                },

                getMaterialCategory(material) {
                    return this.materialCategoryByMaterial[material] || '';
                },

                normalizeText(value) {
                    return (value || '').toString().trim().toLowerCase();
                },

                isBannerLikeCategory(category) {
                    const normalized = this.normalizeText(category);
                    return normalized === 'банер' || normalized === 'банерна сітка';
                },

                getServiceScenario(product) {
                    if (this.isCustomerRollMaterial(product.material)) {
                        return 'customer_roll';
                    }

                    if (this.isCustomerMaterial(product.material)) {
                        return 'sheet';
                    }

                    const materialType = this.getMaterialType(product.material);
                    if (materialType === 'Листовий') {
                        return 'sheet';
                    }

                    if (materialType === 'Рулонний') {
                        return this.isBannerLikeCategory(this.getMaterialCategory(product.material))
                            ? 'roll_banner'
                            : 'roll_other';
                    }

                    return 'default';
                },

                isServiceBlockVisible(product, block) {
                    const scenario = this.getServiceScenario(product);

                    if (scenario === 'sheet') {
                        return !['lamination', 'weeding', 'montage', 'eyelets_soldering'].includes(block);
                    }

                    if (scenario === 'roll_banner') {
                        return !['lamination', 'cutting', 'weeding', 'montage', 'rolling'].includes(block);
                    }

                    if (scenario === 'roll_other') {
                        return block !== 'eyelets_soldering';
                    }

                    return true;
                },

                getCuttingOptions(product) {
                    const scenario = this.getServiceScenario(product);

                    if (scenario === 'sheet') {
                        return ['Фреза', 'Лазер'];
                    }

                    if (scenario === 'roll_other' || scenario === 'customer_roll') {
                        return ['Плотер'];
                    }

                    return ['Фреза', 'Лазер', 'Плотер'];
                },

                ensureCuttingValue(product) {
                    const options = this.getCuttingOptions(product);
                    if (!options.includes(product.services.cutting)) {
                        product.services.cutting = 'Без порізки';
                    }
                },

                showThicknessForMaterial(material) {
                    if (!material) {
                        return false;
                    }

                    if (this.isCustomerMaterial(material)) {
                        return true;
                    }
                    if (this.isCustomerRollMaterial(material)) {
                        return false;
                    }

                    return this.getMaterialType(material) !== 'Рулонний';
                },

                noop() {
                    // Перевірка для УФ друку: у кожній позиції CMYK або Білий мають бути > 0.
                    for (const product of this.products) {
                        if (!this.isUvPrintProduct(product)) {
                            continue;
                        }

                        for (const position of product.positions) {
                            if (!this.isUvLayersValid(position)) {
                                alert('Для "УФ Друк" потрібно вказати значення більше 0 у полі CMYK або Білий для кожної позиції.');
                                return;
                            }
                        }
                    }
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

                    if (!product.material) {
                        product.servicesEnabledRaw = '0';
                    }

                    const options = this.getThicknessOptions(product.material);
                    if (options.length === 1) {
                        product.thickness = options[0];
                    }

                    this.ensureCuttingValue(product);
                },

                onProductTypeChanged(product) {
                    const allowed = this.getAllowedMaterials(product);
                    if (product.material && !allowed.includes(product.material)) {
                        product.material = '';
                    }
                    this.onMaterialChanged(product);
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

                sanitizeUrgencyInput(event) {
                    const raw = String(event.target?.value ?? '');
                    const inputType = String(event?.inputType ?? '');
                    const isDeleting = inputType.startsWith('delete');
                    const digits = raw.replace(/\D/g, '').slice(0, 3);
                    let value = '';

                    if (digits.length === 1) {
                        // Allow full cleanup with Backspace: do not force trailing dot while deleting.
                        value = isDeleting ? `${digits[0]}` : `${digits[0]}.`;
                    } else if (digits.length === 2) {
                        value = `${digits[0]}.${digits[1]}`;
                    } else if (digits.length === 3) {
                        value = `${digits[0]}.${digits[1]}${digits[2]}`;
                    }

                    this.urgencyCoefficient = value;
                    event.target.value = value;
                },

                normalizeUrgencyOnBlur(event) {
                    let value = String(event.target?.value ?? '').trim();
                    if (value === '' || value === '.') {
                        value = '1.00';
                    }

                    const parsed = parseFloat(value);
                    const safeValue = Number.isFinite(parsed) ? parsed : 1;
                    const clamped = Math.min(9.99, Math.max(0.01, safeValue));
                    const formatted = clamped.toFixed(2);

                    this.urgencyCoefficient = formatted;
                    event.target.value = formatted;
                },

                sanitizeDecimalInObject(target, fieldName, event) {
                    target[fieldName] = this.sanitizeDecimalValue(event.target.value);
                    event.target.value = target[fieldName];
                },

                sanitizeIntegerInObject(target, fieldName, event) {
                    target[fieldName] = this.sanitizeIntegerValue(event.target.value);
                    event.target.value = target[fieldName];
                },

                clearDefaultZero(event, type = 'integer') {
                    const raw = String(event.target?.value ?? '');
                    if (type === 'decimal') {
                        if (raw === '0' || raw === '0.0' || raw === '0.00') {
                            event.target.value = '';
                        }
                        return;
                    }

                    if (raw === '0') {
                        event.target.value = '';
                    }
                },

                restoreDefaultOnBlur(target, fieldName, defaultValue, event) {
                    const value = String(event.target?.value ?? '').trim();
                    if (value !== '') {
                        return;
                    }

                    target[fieldName] = defaultValue;
                    event.target.value = defaultValue;
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

                    if (value.includes('.')) {
                        const [intPart, decPart] = value.split('.', 2);
                        const normalizedInt = intPart.replace(/^0+(?=\d)/, '') || '0';
                        value = normalizedInt + '.' + (decPart ?? '');
                    } else {
                        value = value.replace(/^0+(?=\d)/, '');
                    }
                    return value;
                },

                sanitizeIntegerValue(raw) {
                    const digits = String(raw || '').replace(/\D/g, '');
                    if (digits === '') {
                        return '';
                    }
                    return digits.replace(/^0+(?=\d)/, '');
                },
            };
        }
    </script>
</x-app-layout>
