<x-app-layout>
    @section('title', $title)
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $title }}</h2>
            <a href="{{ route('price.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('додати позицію') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12">
            @if (session('status'))
                <div class="mb-4 text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form id="price-bulk-update-form" method="POST" action="{{ route('price.bulk-update') }}">
                        @csrf
                        @method('PATCH')
                    </form>

                    <div class="w-full overflow-x-auto">
                        <table class="min-w-full w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $next = $sort === 'internal_code' && $direction === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('price.index', array_merge(request()->query(), ['sort' => 'internal_code', 'direction' => $next])) }}" class="inline-flex items-center gap-1">
                                            Внутрішній код
                                            @if ($sort === 'internal_code')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $next = $sort === 'name' && $direction === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('price.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => $next])) }}" class="inline-flex items-center gap-1">
                                            Назва товару
                                            @if ($sort === 'name')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $next = $sort === 'category' && $direction === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('price.index', array_merge(request()->query(), ['sort' => 'category', 'direction' => $next])) }}" class="inline-flex items-center gap-1">
                                            Категорія товару
                                            @if ($sort === 'category')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $next = $sort === 'purchase_price' && $direction === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('price.index', array_merge(request()->query(), ['sort' => 'purchase_price', 'direction' => $next])) }}" class="inline-flex items-center gap-1">
                                            Закупівельна ціна
                                            @if ($sort === 'purchase_price')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Націнка %</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $next = $sort === 'service_price' && $direction === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('price.index', array_merge(request()->query(), ['sort' => 'service_price', 'direction' => $next])) }}" class="inline-flex items-center gap-1 justify-center">
                                            Роздрібна ціна
                                            @if ($sort === 'service_price')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Дія</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @if ($items->isEmpty())
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('Записів не знайдено.') }}
                                        </td>
                                    </tr>
                                @endif
                                @foreach ($items as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $item->internal_code }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            <a href="{{ route('price.show', $item) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $item->name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $item->category }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            <input
                                                form="price-bulk-update-form"
                                                type="text"
                                                name="items[{{ $item->id }}][purchase_price]"
                                                value="{{ $item->purchase_price !== null ? number_format((float) $item->purchase_price, 2, '.', '') : '0.00' }}"
                                                class="price-edit-field price-purchase w-28 border-gray-300 rounded-md shadow-sm text-sm"
                                                data-original="{{ $item->purchase_price !== null ? number_format((float) $item->purchase_price, 2, '.', '') : '0.00' }}"
                                                data-row-id="{{ $item->id }}"
                                            />
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700 text-center">
                                            @php
                                                $rowMarkup = '';
                                                if ($item->purchase_price !== null && (float) $item->purchase_price > 0 && $item->service_price !== null) {
                                                    $rowMarkup = number_format((((float) $item->service_price - (float) $item->purchase_price) / (float) $item->purchase_price) * 100, 2, '.', '');
                                                }
                                            @endphp
                                            <input
                                                type="text"
                                                value="{{ $rowMarkup }}"
                                                class="price-edit-field price-markup w-24 border-gray-300 rounded-md shadow-sm text-sm text-center"
                                                data-original="{{ $rowMarkup }}"
                                                data-row-id="{{ $item->id }}"
                                            />
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700 text-center">
                                            <input
                                                form="price-bulk-update-form"
                                                type="text"
                                                name="items[{{ $item->id }}][service_price]"
                                                value="{{ $item->service_price !== null ? number_format((float) $item->service_price, 2, '.', '') : '0.00' }}"
                                                class="price-edit-field price-service w-28 border-gray-300 rounded-md shadow-sm text-sm text-center"
                                                data-original="{{ $item->service_price !== null ? number_format((float) $item->service_price, 2, '.', '') : '0.00' }}"
                                                data-row-id="{{ $item->id }}"
                                            />
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700 text-center">
                                            <form method="POST" action="{{ route('price.toggle', $item) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-gray-600 hover:text-gray-900">
                                                    {{ $item->is_active ? __('Деактивувати') : __('Активувати') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <button type="submit" form="price-bulk-update-form" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md text-sm text-white hover:bg-gray-700">
                            {{ __('Зберегти зміни') }}
                        </button>
                    </div>

                    <div class="mt-4">
                        {{ $items->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const purchaseInputs = Array.from(document.querySelectorAll('.price-purchase'));
            const serviceInputs = Array.from(document.querySelectorAll('.price-service'));
            const markupInputs = Array.from(document.querySelectorAll('.price-markup'));
            let isDirty = false;
            let isSyncing = false;

            const parseNumber = (value) => {
                const normalized = String(value ?? '').replace(',', '.').trim();
                if (normalized === '') return NaN;
                return Number.parseFloat(normalized);
            };

            const round2 = (value) => Math.round(value * 100) / 100;

            const markDirtyState = (input) => {
                const original = String(input.dataset.original ?? '').trim();
                const current = String(input.value ?? '').trim();
                const changed = original !== current;
                input.classList.toggle('text-red-600', changed);
                input.classList.toggle('font-semibold', changed);
                return changed;
            };

            const recalcRow = (rowId, source) => {
                const purchase = document.querySelector(`.price-purchase[data-row-id="${rowId}"]`);
                const service = document.querySelector(`.price-service[data-row-id="${rowId}"]`);
                const markup = document.querySelector(`.price-markup[data-row-id="${rowId}"]`);
                if (!purchase || !service || !markup) return;

                if (isSyncing) return;

                if (source === 'markup') {
                    const p = parseNumber(purchase.value);
                    const m = parseNumber(markup.value);
                    if (Number.isFinite(p) && p > 0 && Number.isFinite(m)) {
                        isSyncing = true;
                        service.value = round2(p * (1 + (m / 100))).toFixed(2);
                        isSyncing = false;
                    }
                } else {
                    const p = parseNumber(purchase.value);
                    const s = parseNumber(service.value);
                    if (Number.isFinite(p) && p > 0 && Number.isFinite(s)) {
                        isSyncing = true;
                        markup.value = round2(((s - p) / p) * 100).toFixed(2);
                        isSyncing = false;
                    } else {
                        markup.value = '';
                    }
                }

                const changed = [purchase, service, markup].some(markDirtyState);
                if (changed) {
                    isDirty = true;
                } else {
                    isDirty = Array.from(document.querySelectorAll('.price-edit-field')).some((el) =>
                        String(el.dataset.original ?? '').trim() !== String(el.value ?? '').trim()
                    );
                }
            };

            purchaseInputs.forEach((input) => {
                input.addEventListener('input', () => recalcRow(input.dataset.rowId, 'purchase'));
            });
            serviceInputs.forEach((input) => {
                input.addEventListener('input', () => recalcRow(input.dataset.rowId, 'service'));
            });
            markupInputs.forEach((input) => {
                input.addEventListener('input', () => recalcRow(input.dataset.rowId, 'markup'));
            });

            window.addEventListener('beforeunload', (event) => {
                if (!isDirty) return;
                event.preventDefault();
                event.returnValue = '';
            });
        })();
    </script>
</x-app-layout>
