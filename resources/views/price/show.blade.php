<x-app-layout>
    @section('title', $title)
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $item->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12 space-y-6">
            @if (session('status'))
                <div class="text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif
            @if ($item->model_type === 'Послуга' && $item->for_customer_material)
                <div class="text-sm font-semibold text-red-600">
                    {{ __('Використовується для розрахунку з матеріалом замовника') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <div class="flex items-end gap-4">
                        <div class="flex-1 min-w-0">
                            <x-input-label :value="__('Назва')" />
                            <x-text-input type="text" class="mt-1 block w-full" :value="$item->name" disabled />
                        </div>
                        <div class="w-44 shrink-0">
                            <x-input-label :value="__('Модел позиції')" />
                            <x-text-input type="text" class="mt-1 block w-full" :value="$item->model_type" disabled />
                        </div>
                        <div class="w-48 shrink-0">
                            <x-input-label :value="__('Внутрішній код')" />
                            <x-text-input type="text" class="mt-1 block w-full" :value="$item->internal_code" disabled />
                        </div>
                    </div>

                    @if ($item->model_type === 'Матеріал')
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="md:col-span-6">
                                <x-input-label :value="__('Категорія')" />
                                <x-text-input type="text" class="mt-1 block w-full" :value="$item->category" disabled />
                            </div>
                            <div class="md:col-span-6">
                                <x-input-label :value="__('Тип')" />
                                <x-text-input type="text" class="mt-1 block w-full" :value="$item->material_type" disabled />
                            </div>
                        </div>
                    @endif

                    <form id="price-item-update-form" method="POST" action="{{ route('price.update', $item) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="md:col-span-4">
                                <x-input-label for="service_price" :value="__('Роздрібна ціна')" />
                                <x-text-input
                                    id="service_price"
                                    name="service_price"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :value="old('service_price', $item->service_price !== null ? number_format((float) $item->service_price, 2, '.', '') : '')"
                                />
                                <x-input-error class="mt-2" :messages="$errors->get('service_price')" />
                            </div>
                            <div class="md:col-span-4">
                                <x-input-label for="purchase_price" :value="__('Закупівельна вартість')" />
                                <x-text-input
                                    id="purchase_price"
                                    name="purchase_price"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :value="old('purchase_price', $item->purchase_price !== null ? number_format((float) $item->purchase_price, 2, '.', '') : '')"
                                />
                                <x-input-error class="mt-2" :messages="$errors->get('purchase_price')" />
                            </div>
                            <div class="md:col-span-4">
                                <x-input-label for="markup_percent" :value="__('Націнка %')" />
                                <x-text-input
                                    id="markup_percent"
                                    type="text"
                                    class="mt-1 block w-full"
                                    value="{{ $item->purchase_price !== null && (float) $item->purchase_price > 0 && $item->service_price !== null
                                        ? number_format((((float) $item->service_price - (float) $item->purchase_price) / (float) $item->purchase_price) * 100, 2, '.', '')
                                        : '' }}"
                                />
                            </div>
                        </div>
                    </form>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div class="md:col-span-4">
                            <x-input-label :value="__('Вимірювання')" />
                            <x-text-input type="text" class="mt-1 block w-full" :value="$item->measurement_unit" disabled />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <button form="price-item-update-form" type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md text-sm text-white hover:bg-gray-700">
                            {{ __('Зберегти') }}
                        </button>
                        <a href="{{ route('price.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-200">
                            {{ __('Повернутись') }}
                        </a>
                        <form method="POST" action="{{ route('price.toggle', $item) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md text-sm text-white hover:bg-gray-700">
                                {{ $item->is_active ? __('Деактивувати') : __('Активувати') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Історія зміни ціни') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Дата</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Роздрібна ціна</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Закупівельна вартість</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Націнка %</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Користувач</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Дія</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($history as $row)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ optional($row->created_at)->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ number_format((float) $row->service_price, 2, '.', '') }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ number_format((float) $row->purchase_price, 2, '.', '') }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->markup_percent !== null ? number_format((float) $row->markup_percent, 2, '.', '') : '' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->user?->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            <form method="POST" action="{{ route('price.history.revert', [$item, $row]) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                    onclick="return confirm('Діюча ціна товару буде оновлена на вибрану')"
                                                >
                                                    {{ __('Повернути') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('Історія порожня.') }}
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
            const servicePriceInput = document.getElementById('service_price');
            const purchasePriceInput = document.getElementById('purchase_price');
            const markupPercentInput = document.getElementById('markup_percent');
            let isSyncing = false;

            const parseNumber = (value) => {
                const normalized = String(value ?? '').replace(',', '.').trim();
                if (normalized === '') return NaN;
                return Number.parseFloat(normalized);
            };

            const round2 = (value) => Math.round(value * 100) / 100;

            const syncMarkup = () => {
                if (isSyncing) return;
                const purchase = parseNumber(purchasePriceInput?.value);
                const service = parseNumber(servicePriceInput?.value);
                if (!Number.isFinite(purchase) || purchase <= 0 || !Number.isFinite(service)) {
                    markupPercentInput.value = '';
                    return;
                }
                markupPercentInput.value = round2(((service - purchase) / purchase) * 100).toFixed(2);
            };

            const syncServicePrice = () => {
                if (isSyncing) return;
                const purchase = parseNumber(purchasePriceInput?.value);
                const markup = parseNumber(markupPercentInput?.value);
                if (!Number.isFinite(purchase) || purchase <= 0 || !Number.isFinite(markup)) return;

                isSyncing = true;
                servicePriceInput.value = round2(purchase * (1 + (markup / 100))).toFixed(2);
                isSyncing = false;
            };

            servicePriceInput?.addEventListener('input', syncMarkup);
            purchasePriceInput?.addEventListener('input', () => {
                syncMarkup();
                syncServicePrice();
            });
            markupPercentInput?.addEventListener('input', syncServicePrice);
        })();
    </script>
</x-app-layout>
