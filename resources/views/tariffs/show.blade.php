<x-app-layout>
    @section('title', $tariff->name ?: __('Картка товару'))
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Картка товару') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12 space-y-6">
            @if (session('status'))
                <div class="text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->has('cross_link'))
                <div class="text-sm text-red-700 bg-red-100 px-4 py-2 rounded">
                    {{ $errors->first('cross_link') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form id="tariff-update-form" method="POST" action="{{ route('tariffs.update', $tariff) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="md:col-span-8">
                                <x-input-label for="name" :value="__('Назва')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $tariff->name) }}" required />
                            </div>
                            <div class="md:col-span-4">
                                <x-input-label for="internal_code" :value="__('Внутрішній код')" />
                                <div class="mt-1 flex items-center gap-3">
                                    <x-text-input id="internal_code" type="text" class="block w-full" value="{{ $tariff->internal_code }}" disabled />
                                    @if ($hasCrossLinks)
                                        <span class="text-sm text-green-600 whitespace-nowrap">
                                            {{ __('Цей товар має кросс-зв\'язок (зв\'язані артикули)') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="md:col-span-4">
                                <x-input-label for="category" :value="__('Категорія')" />
                                <input
                                    id="category"
                                    name="category"
                                    type="text"
                                    list="category-options"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                    value="{{ old('category', $tariff->category) }}"
                                    placeholder="{{ __('Оберіть категорію') }}"
                                >
                                <datalist id="category-options">
                                    @foreach ($productCategories as $category)
                                        <option value="{{ $category }}"></option>
                                    @endforeach
                                </datalist>
                                <x-input-error class="mt-2" :messages="$errors->get('category')" />
                            </div>

                            <div class="md:col-span-4">
                                <x-input-label for="product_group_id" :value="__('Внутрішня назва товару (Група товарів)')" />
                                @php
                                    $currentGroupName = old('product_group_name');
                                    if ($currentGroupName === null) {
                                        $currentGroupName = optional($productGroups->firstWhere('id', $tariff->product_group_id))->name;
                                    }
                                @endphp
                                <input
                                    id="product_group_name"
                                    name="product_group_name"
                                    type="text"
                                    list="product-group-options"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                    value="{{ $currentGroupName }}"
                                    placeholder="{{ __('Оберіть значення') }}"
                                >
                                <datalist id="product-group-options">
                                    @foreach ($productGroups as $group)
                                        <option value="{{ $group->name }}" data-id="{{ $group->id }}"></option>
                                    @endforeach
                                </datalist>
                                <input type="hidden" id="product_group_id" name="product_group_id" value="{{ (int) old('product_group_id', $tariff->product_group_id) ?: '' }}">
                                <x-input-error class="mt-2" :messages="$errors->get('product_group_id')" />
                            </div>

                            <div class="md:col-span-4">
                                <x-text-input
                                    id="material_type_display"
                                    type="text"
                                    class="mt-7 block w-full bg-gray-100 text-gray-700"
                                    value=""
                                    disabled
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                            <div class="md:col-span-3" id="size-width-wrap">
                                <x-input-label for="roll_width_m" :value="__('Ширина (м)')" />
                                <x-text-input id="roll_width_m" name="roll_width_m" type="text" class="mt-1 block w-full" value="{{ old('roll_width_m', $tariff->roll_width_m) }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('roll_width_m')" />
                            </div>
                            <div class="md:col-span-3" id="size-length-wrap">
                                <x-input-label for="roll_length_m" :value="__('Довжина (м)')" />
                                <x-text-input id="roll_length_m" name="roll_length_m" type="text" class="mt-1 block w-full" value="{{ old('roll_length_m', $tariff->roll_length_m) }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('roll_length_m')" />
                            </div>
                            <div class="md:col-span-3" id="size-thickness-wrap">
                                <x-input-label for="sheet_thickness_mm" :value="__('Товщина (мм)')" />
                                <x-text-input id="sheet_thickness_mm" name="sheet_thickness_mm" type="text" class="mt-1 block w-full" value="{{ old('sheet_thickness_mm', $tariff->sheet_thickness_mm) }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('sheet_thickness_mm')" />
                            </div>
                            <div class="md:col-span-3" id="size-auto-fill-wrap">
                                <button type="button" class="inline-flex items-center justify-center w-full px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                                    {{ __('Заповнити параметри розмірів (автоматично)') }}
                                </button>
                            </div>
                        </div>

                        <input type="hidden" name="subcontractor_id" value="{{ $tariff->subcontractor_id }}">

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="md:col-span-4">
                                <x-input-label for="sale_price" :value="__('Роздрібна ціна')" />
                                <x-text-input id="sale_price" name="sale_price" type="text" class="mt-1 block w-full" value="{{ old('sale_price', $tariff->sale_price !== null ? number_format((float) $tariff->sale_price, 2, '.', '') : '') }}" />
                            </div>
                            <div class="md:col-span-4">
                                <x-input-label for="wholesale_price" :value="__('Оптова ціна')" />
                                <x-text-input id="wholesale_price" name="wholesale_price" type="text" class="mt-1 block w-full" value="{{ old('wholesale_price', $tariff->wholesale_price !== null ? number_format((float) $tariff->wholesale_price, 2, '.', '') : '') }}" />
                            </div>
                            <div class="md:col-span-4">
                                <x-input-label for="urgent_price" :value="__('VIP ціна')" />
                                <x-text-input id="urgent_price" name="urgent_price" type="text" class="mt-1 block w-full" value="{{ old('urgent_price', $tariff->urgent_price !== null ? number_format((float) $tariff->urgent_price, 2, '.', '') : '') }}" />
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-gray-700 font-medium">{{ __('Додати кросс-позицію') }}</span>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="radio" name="cross_toggle" value="no" checked>
                                    <span>{{ __('Ні') }}</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="radio" name="cross_toggle" value="yes" id="cross-toggle-yes">
                                    <span>{{ __('Так') }}</span>
                                </label>
                            </div>

                            <div id="cross-link-form" class="hidden space-y-3">
                                <div>
                                    <x-input-label for="cross_supplier_id" :value="__('Постачальник')" />
                                    <select id="cross_supplier_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="">{{ __('Оберіть постачальника') }}</option>
                                        @foreach ($availableSuppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="cross_item_id" :value="__('Товар постачальника')" />
                                    <select id="cross_item_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" disabled>
                                        <option value="">{{ __('Оберіть товар') }}</option>
                                        @foreach ($supplierItems as $item)
                                            <option value="{{ $item->internal_code }}" data-supplier-id="{{ $item->supplier_id }}">
                                                {{ $item->internal_code }} — {{ $item->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex items-center gap-4">
                                    <button type="button" id="save-cross-link" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                        {{ __('Зберегти кросс-зв\'язок') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <x-primary-button>{{ __('Зберегти') }}</x-primary-button>
                                <a href="{{ route('tariffs.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                    {{ __('повернутись') }}
                                </a>
                            </div>
                            <button
                                type="submit"
                                form="tariff-deactivate-form"
                                class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:bg-gray-200 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                onclick="return confirm('Товар буде деактивовано. Продовжити?')"
                            >
                                {{ __('Деактивувати') }}
                            </button>
                        </div>
                    </form>

                    <form id="tariff-deactivate-form" method="POST" action="{{ route('tariffs.deactivate', $tariff) }}" class="hidden">
                        @csrf
                        @method('PATCH')
                    </form>

                    <form method="POST" action="{{ route('tariffs.cross-links.store', $tariff) }}" id="cross-link-submit" class="hidden">
                        @csrf
                        <input type="hidden" name="supplier_id" id="cross_supplier_id_input">
                        <input type="hidden" name="child_internal_code" id="cross_item_input">
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Історія зміни ціни') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">дата</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Закупівельна ціна</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Націнка РЦ %</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Роздрібна ціна</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Націнка Опт %</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Оптова ціна</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Націнка VIP %</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">VIP ціна</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Внутрішній код</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Постачальник</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Користувач</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Дія</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($history as $row)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ optional($row->changed_at)->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->import_price !== null ? number_format((float) $row->import_price, 2, '.', '') : '' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->markup_percent !== null ? number_format((float) $row->markup_percent, 2, '.', '') : '' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->markup_price !== null ? number_format((float) $row->markup_price, 2, '.', '') : '' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->markup_wholesale_percent !== null ? number_format((float) $row->markup_wholesale_percent, 2, '.', '') : '' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->wholesale_price !== null ? number_format((float) $row->wholesale_price, 2, '.', '') : '' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->markup_vip_percent !== null ? number_format((float) $row->markup_vip_percent, 2, '.', '') : '' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->vip_price !== null ? number_format((float) $row->vip_price, 2, '.', '') : '' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->internal_code }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->supplier?->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->user?->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            @if (!empty($row->id))
                                                <form method="POST" action="{{ route('tariffs.history.revert', [$tariff, $row->id]) }}">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="text-indigo-600 hover:text-indigo-900"
                                                        onclick="return confirm('Діюча ціна товару буде оновлена на вибрану')"
                                                    >
                                                        {{ __('Повернути') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('No history yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const yes = document.getElementById('cross-toggle-yes');
            const form = document.getElementById('cross-link-form');
            const supplierSelect = document.getElementById('cross_supplier_id');
            const itemSelect = document.getElementById('cross_item_id');
            const submitButton = document.getElementById('save-cross-link');
            const submitForm = document.getElementById('cross-link-submit');
            const supplierInput = document.getElementById('cross_supplier_id_input');
            const itemInput = document.getElementById('cross_item_input');
            const categorySelect = document.getElementById('category');
            const materialTypeDisplayInput = document.getElementById('material_type_display');
            const materialTypeByCategory = @json($materialTypeByCategory ?? []);
            const productGroupNameInput = document.getElementById('product_group_name');
            const productGroupIdInput = document.getElementById('product_group_id');
            const productGroupOptions = Array.from(document.querySelectorAll('#product-group-options option'));
            const widthWrap = document.getElementById('size-width-wrap');
            const lengthWrap = document.getElementById('size-length-wrap');
            const thicknessWrap = document.getElementById('size-thickness-wrap');
            const autoFillWrap = document.getElementById('size-auto-fill-wrap');
            document.querySelectorAll('input[name="cross_toggle"]').forEach((radio) => {
                radio.addEventListener('change', () => {
                    form.classList.toggle('hidden', !yes.checked);
                });
            });

            supplierSelect?.addEventListener('change', () => {
                const supplierId = supplierSelect.value;
                itemSelect.disabled = !supplierId;
                Array.from(itemSelect.options).forEach((opt) => {
                    if (!opt.value) return;
                    const match = opt.dataset.supplierId === supplierId;
                    opt.hidden = !match;
                    opt.disabled = !match;
                });
                if (itemSelect.selectedOptions.length && itemSelect.selectedOptions[0].disabled) {
                    itemSelect.value = '';
                }
            });

            submitButton?.addEventListener('click', () => {
                if (!supplierSelect.value || !itemSelect.value) {
                    return;
                }
                supplierInput.value = supplierSelect.value;
                itemInput.value = itemSelect.value;
                submitForm.submit();
            });

            const updateSizeFieldsVisibility = () => {
                const value = (categorySelect?.value || '').trim().toLowerCase();

                const isBannerOrFilm = value === 'банер' || value === 'плівка';
                const isAccessoryOrService = value === 'аксесуар' || value === 'послуга';

                if (widthWrap) {
                    widthWrap.classList.toggle('hidden', isAccessoryOrService);
                }
                if (lengthWrap) {
                    lengthWrap.classList.toggle('hidden', isAccessoryOrService);
                }
                if (thicknessWrap) {
                    thicknessWrap.classList.toggle('hidden', isBannerOrFilm || isAccessoryOrService);
                }
                if (autoFillWrap) {
                    autoFillWrap.classList.toggle('hidden', isAccessoryOrService);
                }
            };

            const normalizeValue = (value) => String(value || '').trim().toLowerCase();
            const updateMaterialTypeDisplay = () => {
                if (!categorySelect || !materialTypeDisplayInput) {
                    return;
                }

                const selectedCategory = String(categorySelect.value || '').trim();
                if (selectedCategory === '') {
                    materialTypeDisplayInput.value = '';
                    return;
                }

                const selectedNormalized = normalizeValue(selectedCategory);
                const matchedCategory = Object.keys(materialTypeByCategory).find(
                    (categoryName) => normalizeValue(categoryName) === selectedNormalized
                );

                materialTypeDisplayInput.value = matchedCategory
                    ? (materialTypeByCategory[matchedCategory] || '')
                    : '';
            };

            categorySelect?.addEventListener('change', updateSizeFieldsVisibility);
            categorySelect?.addEventListener('input', updateMaterialTypeDisplay);
            categorySelect?.addEventListener('change', updateMaterialTypeDisplay);
            updateSizeFieldsVisibility();
            updateMaterialTypeDisplay();

            const syncProductGroupId = () => {
                if (!productGroupNameInput || !productGroupIdInput) {
                    return;
                }

                const selected = productGroupOptions.find((option) => option.value === productGroupNameInput.value);
                productGroupIdInput.value = selected?.dataset.id ?? '';
            };

            productGroupNameInput?.addEventListener('input', syncProductGroupId);
            productGroupNameInput?.addEventListener('change', syncProductGroupId);
            syncProductGroupId();
        })();
    </script>
</x-app-layout>
