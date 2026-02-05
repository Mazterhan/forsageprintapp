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
        })();
    </script>
</x-app-layout>
