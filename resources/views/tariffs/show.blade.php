<x-app-layout>
    @section('title', __('Картка товару'))
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Картка товару') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
                    <form method="POST" action="{{ route('tariffs.update', $tariff) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
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

                        <div>
                            <x-input-label for="name" :value="__('Назва')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $tariff->name) }}" required />
                        </div>

                        <div>
                            <x-input-label for="category" :value="__('Категорія')" />
                            <x-text-input id="category" name="category" type="text" class="mt-1 block w-full" value="{{ old('category', $tariff->category) }}" />
                        </div>

                        @php
                            $typeClassValue = old('type_class', $tariff->type_class);
                            $typeClassNormalized = $typeClassValue;
                            if (is_string($typeClassValue)) {
                                if (str_starts_with($typeClassValue, 'sheet_')) {
                                    $typeClassNormalized = 'sheet';
                                } elseif (str_starts_with($typeClassValue, 'roll_')) {
                                    $typeClassNormalized = 'roll';
                                } elseif ($typeClassValue === 'tool_accessory') {
                                    $typeClassNormalized = 'tool';
                                }
                            }
                        @endphp

                        <div>
                            <x-input-label for="type_class" :value="__('Тип/клас товару')" />
                            <select id="type_class" name="type_class" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">{{ __('Оберіть тип') }}</option>
                                <option value="sheet" @selected($typeClassNormalized === 'sheet')>Листові пластики: Акрил, ПВХ, Композит (ACP), ПЕТ, Полістирол</option>
                                <option value="roll" @selected($typeClassNormalized === 'roll')>Плівки (рулонні): Oracal / Orajet / Oralite / Монтажна</option>
                                <option value="tool" @selected($typeClassNormalized === 'tool')>Інструмент/аксесуар</option>
                            </select>
                        </div>

                        <div id="film-block" class="hidden space-y-4">
                            <div>
                                <x-input-label for="film_brand_series" :value="__('Бренд/серія')" />
                                <select id="film_brand_series" name="film_brand_series" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">{{ __('Оберіть серію') }}</option>
                                    <option value="Oracal 641" @selected(old('film_brand_series', $tariff->film_brand_series) === 'Oracal 641')>Oracal 641</option>
                                    <option value="Oracal 640" @selected(old('film_brand_series', $tariff->film_brand_series) === 'Oracal 640')>Oracal 640</option>
                                    <option value="Oracal 8300" @selected(old('film_brand_series', $tariff->film_brand_series) === 'Oracal 8300')>Oracal 8300</option>
                                    <option value="Oracal 352" @selected(old('film_brand_series', $tariff->film_brand_series) === 'Oracal 352')>Oracal 352</option>
                                    <option value="Oracal 6510" @selected(old('film_brand_series', $tariff->film_brand_series) === 'Oracal 6510')>Oracal 6510</option>
                                    <option value="Orajet 3640" @selected(old('film_brand_series', $tariff->film_brand_series) === 'Orajet 3640')>Orajet 3640</option>
                                    <option value="Orajet 3641" @selected(old('film_brand_series', $tariff->film_brand_series) === 'Orajet 3641')>Orajet 3641</option>
                                    <option value="Orajet 3651" @selected(old('film_brand_series', $tariff->film_brand_series) === 'Orajet 3651')>Orajet 3651</option>
                                    <option value="Oralite 5200" @selected(old('film_brand_series', $tariff->film_brand_series) === 'Oralite 5200')>Oralite 5200</option>
                                    <option value="Oralite 5510" @selected(old('film_brand_series', $tariff->film_brand_series) === 'Oralite 5510')>Oralite 5510</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="roll_width_m" :value="__('Ширина рулону (м)')" />
                                    <x-text-input id="roll_width_m" name="roll_width_m" type="text" class="mt-1 block w-full" value="{{ old('roll_width_m', $tariff->roll_width_m) }}" />
                                </div>
                                <div>
                                    <x-input-label for="roll_length_m" :value="__('Довжина (м)')" />
                                    <x-text-input id="roll_length_m" name="roll_length_m" type="text" class="mt-1 block w-full" value="{{ old('roll_length_m', $tariff->roll_length_m) }}" />
                                </div>
                            </div>
                        </div>

                        <div id="sheet-block" class="hidden">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <x-input-label for="sheet_thickness_mm" :value="__('Товщина (мм)')" />
                                    <x-text-input id="sheet_thickness_mm" name="sheet_thickness_mm" type="text" class="mt-1 block w-full" value="{{ old('sheet_thickness_mm', $tariff->sheet_thickness_mm) }}" />
                                </div>
                                <div>
                                    <x-input-label for="sheet_width_mm" :value="__('Ширина (мм)')" />
                                    <x-text-input id="sheet_width_mm" name="sheet_width_mm" type="text" class="mt-1 block w-full" value="{{ old('sheet_width_mm', $tariff->sheet_width_mm) }}" />
                                </div>
                                <div>
                                    <x-input-label for="sheet_length_mm" :value="__('Довжина (мм)')" />
                                    <x-text-input id="sheet_length_mm" name="sheet_length_mm" type="text" class="mt-1 block w-full" value="{{ old('sheet_length_mm', $tariff->sheet_length_mm) }}" />
                                </div>
                            </div>
                        </div>

                        <div id="attribute-block" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="color" :value="__('Колір')" />
                                <input id="color" name="color" list="color-options" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" value="{{ old('color', $tariff->color) }}" />
                                <datalist id="color-options">
                                    <option value="блакитний"></option>
                                    <option value="світло-блакитний"></option>
                                    <option value="небесно-блакитний"></option>
                                    <option value="синій"></option>
                                    <option value="яскраво-синій"></option>
                                    <option value="королівський синій"></option>
                                    <option value="темно-синій"></option>
                                    <option value="лазурний"></option>
                                    <option value="бірюзовий"></option>
                                    <option value="зелений"></option>
                                    <option value="світло-зелений"></option>
                                    <option value="липово-зелений"></option>
                                    <option value="жовто-зелений"></option>
                                    <option value="трав'янисто-зелений"></option>
                                    <option value="жовтий"></option>
                                    <option value="золотисто-жовтий"></option>
                                    <option value="сірчано-жовтий"></option>
                                    <option value="червоний"></option>
                                    <option value="темно-червоний"></option>
                                    <option value="помаранчевий"></option>
                                    <option value="пастельно-помаранчевий"></option>
                                    <option value="фіоглетовий"></option>
                                    <option value="малиновий"></option>
                                    <option value="лавандовий"></option>
                                    <option value="світло-рожевий"></option>
                                    <option value="чорний"></option>
                                    <option value="темно-сірий"></option>
                                    <option value="білий"></option>
                                    <option value="бежевий"></option>
                                    <option value="світло-коричневий"></option>
                                    <option value="коричневий"></option>
                                    <option value="пурпурний"></option>
                                    <option value="бургунд"></option>
                                    <option value="золото"></option>
                                    <option value="срібло"></option>
                                </datalist>
                            </div>
                            <div>
                                <x-input-label for="finish" :value="__('Поверхня/фініш')" />
                                <select id="finish" name="finish" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">{{ __('Оберіть варіант') }}</option>
                                    <option value="мат" @selected(old('finish', $tariff->finish) === 'мат')>мат</option>
                                    <option value="глянець" @selected(old('finish', $tariff->finish) === 'глянець')>глянець</option>
                                    <option value="дзеркало" @selected(old('finish', $tariff->finish) === 'дзеркало')>дзеркало</option>
                                    <option value="браш (brush)" @selected(old('finish', $tariff->finish) === 'браш (brush)')>браш (brush)</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="special_effect" :value="__('Спецефекти')" />
                                <select id="special_effect" name="special_effect" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">{{ __('Оберіть варіант') }}</option>
                                    <option value="прозорий" @selected(old('special_effect', $tariff->special_effect) === 'прозорий')>прозорий</option>
                                    <option value="молочний" @selected(old('special_effect', $tariff->special_effect) === 'молочний')>молочний</option>
                                    <option value="опал" @selected(old('special_effect', $tariff->special_effect) === 'опал')>опал</option>
                                    <option value="дзеркало" @selected(old('special_effect', $tariff->special_effect) === 'дзеркало')>дзеркало</option>
                                    <option value="флуоресцентний" @selected(old('special_effect', $tariff->special_effect) === 'флуоресцентний')>флуоресцентний</option>
                                    <option value="світловідбиваюча" @selected(old('special_effect', $tariff->special_effect) === 'світловідбиваюча')>світловідбиваюча</option>
                                    <option value="дорожній" @selected(old('special_effect', $tariff->special_effect) === 'дорожній')>дорожній</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="liner" :value="__('Підложка/лайнер')" />
                                <select id="liner" name="liner" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">{{ __('Оберіть варіант') }}</option>
                                    <option value="сіра підложка" @selected(old('liner', $tariff->liner) === 'сіра підложка')>сіра підложка</option>
                                    <option value="біла основа" @selected(old('liner', $tariff->liner) === 'біла основа')>біла основа</option>
                                    <option value="чорна основа" @selected(old('liner', $tariff->liner) === 'чорна основа')>чорна основа</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="double_sided" :value="__('Двосторонність')" />
                                <select id="double_sided" name="double_sided" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="односторонній" @selected(old('double_sided', $tariff->double_sided ?? 'односторонній') === 'односторонній')>односторонній</option>
                                    <option value="двосторонній" @selected(old('double_sided', $tariff->double_sided) === 'двосторонній')>двосторонній</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="subcontractor_id" :value="__('Субпідрядник')" />
                            <select id="subcontractor_id" name="subcontractor_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">{{ __('Select') }}</option>
                                @foreach ($subcontractors as $subcontractor)
                                    <option value="{{ $subcontractor->id }}" @selected($tariff->subcontractor_id === $subcontractor->id)>
                                        {{ $subcontractor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="sale_price" :value="__('Роздрібна ціна')" />
                            <x-text-input id="sale_price" name="sale_price" type="text" class="mt-1 block w-full" value="{{ old('sale_price', $tariff->sale_price !== null ? number_format((float) $tariff->sale_price, 2, '.', '') : '') }}" />
                        </div>
                        <div>
                            <x-input-label for="wholesale_price" :value="__('Оптова ціна')" />
                            <x-text-input id="wholesale_price" name="wholesale_price" type="text" class="mt-1 block w-full" value="{{ old('wholesale_price', $tariff->wholesale_price !== null ? number_format((float) $tariff->wholesale_price, 2, '.', '') : '') }}" />
                        </div>
                        <div>
                            <x-input-label for="urgent_price" :value="__('Термінова робота')" />
                            <x-text-input id="urgent_price" name="urgent_price" type="text" class="mt-1 block w-full" value="{{ old('urgent_price', $tariff->urgent_price !== null ? number_format((float) $tariff->urgent_price, 2, '.', '') : '') }}" />
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

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Зберегти') }}</x-primary-button>
                            <a href="{{ route('tariffs.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('повернутись') }}
                            </a>
                        </div>
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
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Націнка %</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ціна з націнкою</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Внутрішній код</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Постачальник</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Користувач</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($history as $row)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ optional($row->changed_at)->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->import_price }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->markup_percent }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->markup_price }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->internal_code }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->supplier?->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->user?->name }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
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
            const typeSelect = document.getElementById('type_class');
            const filmBlock = document.getElementById('film-block');
            const sheetBlock = document.getElementById('sheet-block');
            const attributeBlock = document.getElementById('attribute-block');

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

            const updateTypeBlocks = () => {
                const value = typeSelect?.value || '';
                const isFilm = value === 'roll';
                const isSheet = value === 'sheet';
                const isTool = value === 'tool';
                filmBlock?.classList.toggle('hidden', !isFilm);
                sheetBlock?.classList.toggle('hidden', !isSheet);
                attributeBlock?.classList.toggle('hidden', isTool || !value);
            };

            typeSelect?.addEventListener('change', updateTypeBlocks);
            updateTypeBlocks();
        })();
    </script>
</x-app-layout>
