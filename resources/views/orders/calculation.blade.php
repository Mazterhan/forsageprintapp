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
                class="space-y-5 overflow-visible"
                x-data="orderCalculation({
                    clients: @js($clients),
                    productTypes: @js($productTypes),
                    materials: @js($materials),
                    thicknessByMaterial: @js($thicknessByMaterial),
                    materialTypeByMaterial: @js($materialTypeByMaterial),
                    materialCategoryByMaterial: @js($materialCategoryByMaterial),
                    materialCategoriesByMaterial: @js($materialCategoriesByMaterial),
                    materialPriceByMaterial: @js($materialPriceByMaterial),
                    materialCodeByMaterial: @js($materialCodeByMaterial),
                    servicePriceByCode: @js($servicePriceByCode),
                    typeCategoryMatrix: @js($typeCategoryMatrix),
                })"
            >
                <div class="sticky top-0 z-[9999] isolate overflow-visible border-2 border-gray-700 rounded-lg p-4 shadow-sm" style="background-color: #FCEEDF;">
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="text-sm font-semibold text-gray-700">Замовник</div>
                        <div class="min-w-[260px] flex-1">
                            <div class="relative z-[10000] overflow-visible" @click.outside="showClientDropdown = false">
                                <div class="relative overflow-hidden rounded-md">
                                    <input
                                        x-model="selectedClientQuery"
                                        @input="onClientInputChanged(); showClientDropdown = true"
                                        @focus="showClientDropdown = true"
                                        @keydown.escape="showClientDropdown = false"
                                        @blur="handleClientInputBlur()"
                                        type="text"
                                        autocomplete="off"
                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full pr-10 text-left"
                                        placeholder="Оберіть замовника"
                                    />
                                    <button
                                        type="button"
                                        @click="showClientDropdown = !showClientDropdown"
                                        class="absolute inset-y-0 right-0 z-10 flex w-10 items-center justify-center rounded-r-md border-l border-gray-200 bg-white text-gray-500 hover:text-gray-700"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.118l3.71-3.887a.75.75 0 111.08 1.04l-4.25 4.455a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                                <div
                                    x-show="showClientDropdown"
                                    x-transition
                                    class="absolute z-[10001] mt-1 w-full rounded-md border border-gray-300 bg-white shadow-lg max-h-64 overflow-auto text-left"
                                >
                                    <template x-if="getFilteredClients().length === 0">
                                        <div class="px-3 py-2 text-sm text-gray-500">Нічого не знайдено</div>
                                    </template>
                                    <template x-for="client in getFilteredClients()" :key="`client-option-${client.id}`">
                                        <button
                                            type="button"
                                            @mousedown.prevent="selectClient(client)"
                                            class="flex w-full justify-start px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                            x-text="client.name"
                                        ></button>
                                    </template>
                                </div>
                            </div>
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
                        <div x-show="product.isExpanded" class="relative z-0 border border-gray-300 rounded-lg p-4 space-y-4 bg-white">
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
                                <div style="width: 330px;">
                                    <div class="relative" @click.outside="product.showMaterialDropdown = false">
                                        <div class="mt-1 flex items-stretch overflow-hidden rounded-md border border-gray-300 bg-white shadow-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                                            <input
                                                x-model="product.materialQuery"
                                                @input="onMaterialInputChanged(product); product.showMaterialDropdown = true"
                                                @focus="if (product.productTypeId) product.showMaterialDropdown = true"
                                                @keydown.escape="product.showMaterialDropdown = false"
                                                @blur="handleMaterialInputBlur(product)"
                                                type="text"
                                                autocomplete="off"
                                                :disabled="!product.productTypeId"
                                                class="block w-full border-0 bg-transparent pr-3 text-left focus:border-transparent focus:ring-0 disabled:bg-gray-100 disabled:text-gray-500"
                                                placeholder="Оберіть матеріал"
                                            />
                                            <button
                                                type="button"
                                                @click="if (product.productTypeId) { product.showMaterialDropdown = !product.showMaterialDropdown }"
                                                :disabled="!product.productTypeId"
                                                class="flex w-10 shrink-0 items-center justify-center border-l border-gray-300 bg-gray-50 text-gray-600 hover:bg-gray-100 hover:text-gray-800 disabled:bg-gray-100 disabled:text-gray-400"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.118l3.71-3.887a.75.75 0 111.08 1.04l-4.25 4.455a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div
                                            x-show="product.productTypeId && product.showMaterialDropdown"
                                            x-transition
                                            class="absolute z-[300] mt-1 w-full rounded-md border border-gray-300 bg-white shadow-sm max-h-64 overflow-auto text-left"
                                        >
                                            <template x-if="getFilteredMaterials(product).length === 0">
                                                <div class="px-3 py-2 text-sm text-gray-500">Нічого не знайдено</div>
                                            </template>
                                            <template x-for="material in getFilteredMaterials(product)" :key="`material-option-${product.uid}-${material}`">
                                                <button
                                                    type="button"
                                                    @mousedown.prevent="selectMaterial(product, material)"
                                                    class="flex w-full justify-start px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                                    x-text="material"
                                                ></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

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
                                            <input type="text" :value="formatMoney(getPositionCost(product, position))" disabled class="w-[110px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700">
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
                                        <template x-if="getMaterialType(product.material) === 'Листовий'">
                                            <div class="ml-4 flex items-center gap-3">
                                                <div class="text-sm text-gray-700 whitespace-nowrap">Товщина обраного матеріалу (мм)</div>
                                                <input
                                                    type="text"
                                                    :value="getSelectedMaterialThickness(product)"
                                                    disabled
                                                    class="w-[120px] border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700"
                                                />
                                            </div>
                                        </template>
                                        <template x-if="showThicknessForMaterial(product.material)">
                                            <div class="ml-4 flex items-center gap-3">
                                                <div class="text-sm text-gray-700 whitespace-nowrap">Товщина матеріалу (мм)</div>
                                                <div class="w-[220px]">
                                                    <template x-if="!isCustomerMaterial(product.material)">
                                                        <select
                                                            x-model="product.thickness"
                                                            :disabled="!product.productTypeId || isSingleThicknessOption(product.material) || product.services.cutting === 'Без порізки'"
                                                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full disabled:bg-gray-100 disabled:text-gray-500"
                                                        >
                                                            <option value="">Оберіть товщину</option>
                                                            <template x-for="thicknessValue in getThicknessOptions(product.material)" :key="thicknessValue">
                                                                <option :value="thicknessValue" x-text="thicknessValue"></option>
                                                            </template>
                                                        </select>
                                                    </template>
                                                    <template x-if="isCustomerMaterial(product.material)">
                                                        <div class="space-y-1">
                                                            <div class="flex items-center gap-3 min-w-0">
                                                                <input
                                                                    x-model="product.manualThickness"
                                                                    @input="onManualThicknessInput(product, $event)"
                                                                    @blur="onManualThicknessBlur(product, $event)"
                                                                    type="text"
                                                                    inputmode="decimal"
                                                                    :disabled="!product.productTypeId || product.services.cutting === 'Без порізки'"
                                                                    class="w-[90px] border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block shrink-0 disabled:bg-gray-100 disabled:text-gray-500"
                                                                />
                                                                <span
                                                                    x-show="product.services.cutting !== 'Без порізки' && !String(product.manualThickness || '').trim()"
                                                                    class="text-sm font-semibold text-red-600 whitespace-nowrap"
                                                                >
                                                                    Для листового матеріалу замовника необхідно вказати товщину у мм.
                                                                </span>
                                                            </div>
                                                            <p x-show="product.manualThicknessError" class="text-xs text-red-600" x-text="product.manualThicknessError"></p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
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
                materialPriceByMaterial: config.materialPriceByMaterial || {},
                materialCodeByMaterial: config.materialCodeByMaterial || {},
                servicePriceByCode: config.servicePriceByCode || {},
                typeCategoryMatrix: config.typeCategoryMatrix || {},
                selectedClientId: '',
                selectedClientQuery: '',
                showClientDropdown: false,
                urgencyCoefficient: '1.00',
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
                        materialQuery: '',
                        showMaterialDropdown: false,
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
                        this.selectedClientQuery = client.name || '';
                    }
                },

                onClientInputChanged() {
                    const query = String(this.selectedClientQuery || '').trim();
                    if (query === '') {
                        this.selectedClientId = '';
                        this.onClientChanged();
                        return;
                    }

                    const normalize = (value) => this.normalizeForCompare(value);
                    const exact = this.clients.find((client) => normalize(client.name) === normalize(query));
                    if (exact) {
                        this.selectedClientId = String(exact.id);
                        this.onClientChanged();
                        return;
                    }

                    // Manual input is allowed; pricing lock applies only when a known client is matched.
                    this.selectedClientId = '';
                    this.onClientChanged();
                },

                handleClientInputBlur() {
                    this.applyClientAutoMatch();
                    setTimeout(() => {
                        this.showClientDropdown = false;
                    }, 120);
                },

                getFilteredClients() {
                    const normalize = (value) => this.normalizeForCompare(value);
                    const query = normalize(this.selectedClientQuery || '');
                    if (!query) {
                        return this.clients.slice(0, 50);
                    }

                    return this.clients
                        .filter((client) => normalize(client.name).includes(query))
                        .slice(0, 50);
                },

                selectClient(client) {
                    this.selectedClientQuery = client.name || '';
                    this.selectedClientId = String(client.id);
                    this.onClientChanged();
                    this.showClientDropdown = false;
                },

                applyClientAutoMatch() {
                    const query = String(this.selectedClientQuery || '').trim();
                    if (query === '') {
                        this.selectedClientId = '';
                        this.onClientChanged();
                        return;
                    }

                    const normalize = (value) => this.normalizeForCompare(value);
                    const normalizedQuery = normalize(query);
                    const exact = this.clients.find((client) => normalize(client.name) === normalizedQuery);
                    if (exact) {
                        this.selectedClientId = String(exact.id);
                        this.onClientChanged();
                        return;
                    }

                    const startsWithMatches = this.clients.filter((client) =>
                        normalize(client.name).startsWith(normalizedQuery)
                    );
                    if (startsWithMatches.length === 1) {
                        this.selectedClientId = String(startsWithMatches[0].id);
                        this.onClientChanged();
                        return;
                    }

                    const containsMatches = this.clients.filter((client) =>
                        normalize(client.name).includes(normalizedQuery)
                    );
                    if (containsMatches.length === 1) {
                        this.selectedClientId = String(containsMatches[0].id);
                        this.onClientChanged();
                        return;
                    }

                    // Keep manual value when match is ambiguous or not found.
                    this.selectedClientId = '';
                    this.onClientChanged();
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

                    const materialCategory = this.normalizeText(this.getMaterialCategory(material));
                    if (materialCategory === 'скотч') {
                        return false;
                    }

                    if (this.isFilmMaterialRestrictedByType(material) && !this.isProductType(product.productTypeId, 'Сольвентний друк')) {
                        return false;
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
                    // Fallback: if matrix is not configured for the selected product type,
                    // do not hide tariff materials completely.
                    if (Object.keys(matrixForType).length === 0) {
                        return true;
                    }

                    return categories.some((categoryName) => matrixForType[categoryName] === true);
                },

                getAllowedMaterials(product) {
                    return this.materials.filter((material) => this.isMaterialAllowedForProductType(product, material));
                },

                getFilteredMaterials(product) {
                    const allowed = this.getAllowedMaterials(product);
                    const query = this.normalizeForCompare(product.materialQuery || '');
                    if (!query) {
                        return allowed.slice(0, 50);
                    }

                    return allowed
                        .filter((material) => this.normalizeForCompare(material).includes(query))
                        .slice(0, 50);
                },

                onMaterialInputChanged(product) {
                    if (!product.productTypeId) {
                        return;
                    }

                    const query = String(product.materialQuery || '').trim();
                    const allowed = this.getAllowedMaterials(product);

                    if (query === '') {
                        if (product.material !== '') {
                            product.material = '';
                            this.onMaterialChanged(product);
                        }
                        return;
                    }

                    const exact = allowed.find((material) =>
                        this.normalizeForCompare(material) === this.normalizeForCompare(query)
                    );

                    if (exact) {
                        if (product.material !== exact) {
                            product.material = exact;
                            this.onMaterialChanged(product);
                        }
                        product.materialQuery = exact;
                        return;
                    }

                    if (product.material !== '') {
                        product.material = '';
                        this.onMaterialChanged(product);
                    }
                },

                handleMaterialInputBlur(product) {
                    this.applyMaterialAutoMatch(product);
                    setTimeout(() => {
                        product.showMaterialDropdown = false;
                    }, 120);
                },

                selectMaterial(product, material) {
                    product.material = material;
                    product.materialQuery = material;
                    this.onMaterialChanged(product);
                    product.showMaterialDropdown = false;
                },

                applyMaterialAutoMatch(product) {
                    const query = String(product.materialQuery || '').trim();
                    const allowed = this.getAllowedMaterials(product);

                    if (query === '') {
                        if (product.material !== '') {
                            product.material = '';
                            this.onMaterialChanged(product);
                        }
                        return;
                    }

                    const normalize = (value) => this.normalizeForCompare(value);
                    const normalizedQuery = normalize(query);

                    const exact = allowed.find((material) => normalize(material) === normalizedQuery);
                    if (exact) {
                        if (product.material !== exact) {
                            product.material = exact;
                            this.onMaterialChanged(product);
                        }
                        product.materialQuery = exact;
                        return;
                    }

                    const startsWithMatches = allowed.filter((material) => normalize(material).startsWith(normalizedQuery));
                    if (startsWithMatches.length === 1) {
                        product.material = startsWithMatches[0];
                        product.materialQuery = startsWithMatches[0];
                        this.onMaterialChanged(product);
                        return;
                    }

                    const containsMatches = allowed.filter((material) => normalize(material).includes(normalizedQuery));
                    if (containsMatches.length === 1) {
                        product.material = containsMatches[0];
                        product.materialQuery = containsMatches[0];
                        this.onMaterialChanged(product);
                        return;
                    }

                    if (product.material !== '') {
                        product.material = '';
                        this.onMaterialChanged(product);
                    }
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

                getMaterialCode(material) {
                    return this.materialCodeByMaterial[material] || '';
                },

                isFilmMaterialRestrictedByType(material) {
                    const code = this.getMaterialCode(material);
                    return code === 'MAT-FLM-010' || code === 'MAT-FLM-011';
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
                    const materialCode = this.getMaterialCode(product.material);

                    if (block === 'lamination' && (materialCode === 'MAT-FLM-010' || materialCode === 'MAT-FLM-011')) {
                        return false;
                    }

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
                    const materialCategory = this.normalizeText(this.getMaterialCategory(product.material));

                    let options = [];

                    if (scenario === 'sheet') {
                        options = ['Фреза', 'Лазер'];
                    } else if (scenario === 'roll_other' || scenario === 'customer_roll') {
                        options = ['Плотер'];
                    } else {
                        options = ['Фреза', 'Лазер', 'Плотер'];
                    }

                    // Business restrictions by selected material category:
                    // - Картон: Фреза недоступна
                    // - ПВХ або Композит: Лазер недоступний
                    if (materialCategory === 'картон') {
                        options = options.filter((option) => option !== 'Фреза');
                    }

                    if (materialCategory === 'пвх' || materialCategory === 'композит') {
                        options = options.filter((option) => option !== 'Лазер');
                    }

                    return options;
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

                    return this.isCustomerMaterial(material);
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

                getSelectedMaterialThickness(product) {
                    if (!product?.material || this.getMaterialType(product.material) !== 'Листовий') {
                        return '';
                    }
                    const selected = String(product.thickness ?? '').trim();
                    if (selected !== '') {
                        return selected;
                    }
                    const options = this.getThicknessOptions(product.material);
                    if (Array.isArray(options) && options.length === 1) {
                        return String(options[0] ?? '').trim();
                    }
                    return '';
                },

                isSingleThicknessOption(material) {
                    return this.getThicknessOptions(material).length === 1;
                },

                onMaterialChanged(product) {
                    product.thickness = '';
                    product.manualThickness = '';
                    product.manualThicknessError = '';
                    if (product.material) {
                        product.materialQuery = product.material;
                    }

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
                    const selectedProductTypeId = String(product.productTypeId || '');
                    const resetProduct = this.createProduct();

                    product.productTypeId = selectedProductTypeId;
                    product.material = resetProduct.material;
                    product.materialQuery = resetProduct.materialQuery;
                    product.showMaterialDropdown = false;
                    product.thickness = resetProduct.thickness;
                    product.manualThickness = resetProduct.manualThickness;
                    product.manualThicknessError = resetProduct.manualThicknessError;
                    product.positions = [this.createPosition()];
                    product.servicesEnabledRaw = resetProduct.servicesEnabledRaw;
                    product.services = { ...resetProduct.services };

                    this.ensureCuttingValue(product);
                },

                onManualThicknessInput(product, event) {
                    const value = this.sanitizeThicknessValue(event.target.value);
                    product.manualThickness = value;
                    event.target.value = value;

                    if (value === '') {
                        product.manualThicknessError = '';
                        return;
                    }

                    product.manualThicknessError = '';
                },

                onManualThicknessBlur(product, event) {
                    const value = this.sanitizeThicknessValue(event.target.value);
                    product.manualThickness = value;
                    event.target.value = value;
                    product.manualThicknessError = '';
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

                trimDecimalDisplay(value) {
                    const normalized = String(value ?? '').trim();
                    if (normalized === '') {
                        return '';
                    }
                    return normalized.replace(/(\.\d*?[1-9])0+$/, '$1').replace(/\.0+$/, '');
                },

                sanitizeThicknessValue(raw) {
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
                    return this.trimDecimalDisplay(value);
                },

                sanitizeIntegerValue(raw) {
                    const digits = String(raw || '').replace(/\D/g, '');
                    if (digits === '') {
                        return '';
                    }
                    return digits.replace(/^0+(?=\d)/, '');
                },

                toNumber(value) {
                    const normalized = String(value ?? '').replace(',', '.').trim();
                    if (normalized === '') {
                        return NaN;
                    }
                    const parsed = parseFloat(normalized);
                    return Number.isFinite(parsed) ? parsed : NaN;
                },

                normalizeMoney(value) {
                    return Math.round((value + Number.EPSILON) * 100) / 100;
                },

                formatMoney(value) {
                    if (!Number.isFinite(value)) {
                        return '';
                    }
                    return this.normalizeMoney(value).toFixed(2);
                },

                getUrgencyValue() {
                    const value = this.toNumber(this.urgencyCoefficient);
                    return Number.isFinite(value) && value > 0 ? value : NaN;
                },

                getMaterialPrice(material) {
                    if (!material) {
                        return NaN;
                    }

                    const price = this.materialPriceByMaterial[material];
                    if (price === undefined || price === null) {
                        return NaN;
                    }

                    const parsed = this.toNumber(price);
                    return Number.isFinite(parsed) ? parsed : NaN;
                },

                getServicePriceByCode(code) {
                    const price = this.servicePriceByCode?.[code];
                    const parsed = this.toNumber(price);
                    return Number.isFinite(parsed) ? parsed : 0;
                },

                getPositionAreaQty(position) {
                    const width = this.toNumber(position.width);
                    const height = this.toNumber(position.height);
                    const qty = this.toNumber(position.qty);

                    if (!Number.isFinite(width) || !Number.isFinite(height) || !Number.isFinite(qty)) {
                        return NaN;
                    }

                    return width * height * qty;
                },

                resolveMaterialPriceForProduct(product) {
                    if (this.isCustomerMaterial(product.material) || this.isCustomerRollMaterial(product.material)) {
                        return 0;
                    }

                    return this.getMaterialPrice(product.material);
                },

                getPositionCost(product, position) {
                    if (!product?.productTypeId || !product?.material) {
                        return NaN;
                    }

                    const areaQty = this.getPositionAreaQty(position);
                    const urgency = this.getUrgencyValue();
                    if (!Number.isFinite(areaQty) || !Number.isFinite(urgency)) {
                        return NaN;
                    }

                    const isUv = this.isProductType(product.productTypeId, 'УФ друк');
                    const isSolvent = this.isProductType(product.productTypeId, 'Сольвентний друк');
                    const isCutOnly = this.isProductType(product.productTypeId, 'Чиста порізка');

                    let baseUnitPrice = NaN;

                    if (isUv) {
                        const cmyk = this.toNumber(position.cmyk);
                        const white = this.toNumber(position.white);
                        if (!Number.isFinite(cmyk) || !Number.isFinite(white)) {
                            return NaN;
                        }

                        const uvPrintLayerPrice = this.getServicePriceByCode('SERV-011');
                        const materialPrice = this.resolveMaterialPriceForProduct(product);
                        if (!Number.isFinite(materialPrice)) {
                            return NaN;
                        }

                        baseUnitPrice = ((cmyk + white) * uvPrintLayerPrice) + materialPrice;
                    } else if (isSolvent) {
                        const solventServicePrice = this.getServicePriceByCode('SERV-012');
                        let materialPrice = this.getMaterialPrice(product.material);
                        if (this.isCustomerRollMaterial(product.material)) {
                            materialPrice = 0;
                        }
                        if (!Number.isFinite(materialPrice)) {
                            return NaN;
                        }

                        baseUnitPrice = solventServicePrice + materialPrice;
                    } else if (isCutOnly) {
                        const materialPrice = this.resolveMaterialPriceForProduct(product);
                        if (!Number.isFinite(materialPrice)) {
                            return NaN;
                        }

                        baseUnitPrice = materialPrice;
                    } else {
                        return NaN;
                    }

                    return this.normalizeMoney(baseUnitPrice * areaQty * urgency);
                },
            };
        }
    </script>
</x-app-layout>
