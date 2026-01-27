<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Ціноутворення') }}
            </h2>
            <a href="{{ route('pricing.subcontractors.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('Підрядні організації') }}
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
            @if ($errors->has('selected'))
                <div class="mb-4 text-sm text-red-700 bg-red-100 px-4 py-2 rounded">
                    {{ $errors->first('selected') }}
                </div>
            @endif

            <form method="POST" action="{{ route('pricing.apply.bulk') }}">
                @csrf

                <div class="flex justify-between items-center mb-3">
                    <div class="text-sm text-gray-500">
                        {{ __('Товари, що очікують зміни ціни для прайс-листа:') }}
                    </div>
                    <x-primary-button>{{ __('Застосувати для виділених') }}</x-primary-button>
                </div>

                @php
                    $currentSort = request('sort');
                    $currentDirection = request('direction', 'asc');
                @endphp
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full">
                    <div class="p-6 text-gray-900">
                        <div class="overflow-x-auto w-full">
                            <table class="w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Внутрішній код</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            @php
                                                $nextDirection = $currentSort === 'name' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                            @endphp
                                            <a href="{{ route('pricing.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                                Назва
                                                @if ($currentSort === 'name')
                                                    <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                                @else
                                                    <span class="text-gray-400">↕</span>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            @php
                                                $nextDirection = $currentSort === 'category' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                            @endphp
                                            <a href="{{ route('pricing.index', array_merge(request()->query(), ['sort' => 'category', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                                Категорія
                                                @if ($currentSort === 'category')
                                                    <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                                @else
                                                    <span class="text-gray-400">↕</span>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            @php
                                                $nextDirection = $currentSort === 'subcontractor' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                            @endphp
                                            <a href="{{ route('pricing.index', array_merge(request()->query(), ['sort' => 'subcontractor', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                                Субпідрядник
                                                @if ($currentSort === 'subcontractor')
                                                    <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                                @else
                                                    <span class="text-gray-400">↕</span>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            @php
                                                $nextDirection = $currentSort === 'import_price' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                            @endphp
                                            <a href="{{ route('pricing.index', array_merge(request()->query(), ['sort' => 'import_price', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                                Закупівельна ціна
                                                @if ($currentSort === 'import_price')
                                                    <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                                @else
                                                    <span class="text-gray-400">↕</span>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Націнка %</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Роздрібна ціна</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            @php
                                                $nextDirection = $currentSort === 'imported_at' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                            @endphp
                                            <a href="{{ route('pricing.index', array_merge(request()->query(), ['sort' => 'imported_at', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                                Дата імпорту
                                                @if ($currentSort === 'imported_at')
                                                    <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                                @else
                                                    <span class="text-gray-400">↕</span>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Дія</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse ($items as $item)
                                        <tr data-import-price="{{ $item->import_price ?? 0 }}">
                                            <td class="px-4 py-2 text-sm text-gray-700">
                                                <input type="checkbox" name="selected[]" value="{{ $item->id }}" class="rounded border-gray-300">
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-700">
                                                <a href="{{ route('pricing.items.show', $item) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    {{ $item->internal_code }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-700">
                                                <a href="{{ route('pricing.items.show', $item) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    {{ $item->name }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $item->category }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">
                                                <select name="subcontractor_id[{{ $item->id }}]" class="border-gray-300 rounded-md shadow-sm text-sm">
                                                    <option value="">{{ __('Select') }}</option>
                                                    @foreach ($subcontractors as $subcontractor)
                                                        <option value="{{ $subcontractor->id }}" @selected($item->subcontractor_id === $subcontractor->id)>
                                                            {{ $subcontractor->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $item->import_price !== null ? number_format((float) $item->import_price, 2, '.', '') : '' }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">
                                                <input type="text" name="markup_percent[{{ $item->id }}]" value="{{ $item->markup_percent ?? 30 }}" class="w-20 border-gray-300 rounded-md shadow-sm text-sm markup-percent">
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-700">
                                                <input type="text" name="markup_price[{{ $item->id }}]" value="{{ $item->markup_price !== null ? number_format((float) $item->markup_price, 2, '.', '') : '' }}" class="w-24 border-gray-300 rounded-md shadow-sm text-sm markup-price">
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ optional($item->last_changed_at)->format('Y-m-d H:i') }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">
                                                <div class="flex items-center gap-3">
                                                    <button type="submit" formaction="{{ route('pricing.apply.single', $item) }}" class="text-indigo-600 hover:text-indigo-900">
                                                        {{ __('Застосувати') }}
                                                    </button>
                                                    <button type="submit" formaction="{{ route('pricing.items.deactivate', $item) }}" class="text-gray-600 hover:text-gray-900">
                                                        {{ __('Деактивувати') }}
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="px-4 py-6 text-center text-sm text-gray-500">
                                                {{ __('No pricing items found.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $items->links() }}
                        </div>

                        <div class="mt-4 flex justify-end">
                            <x-primary-button>{{ __('Застосувати для виділених') }}</x-primary-button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('tr[data-import-price]').forEach((row) => {
            const importPrice = parseFloat(row.getAttribute('data-import-price')) || 0;
            const percentInput = row.querySelector('.markup-percent');
            const priceInput = row.querySelector('.markup-price');

            if (percentInput && priceInput) {
                percentInput.addEventListener('input', () => {
                    const percent = parseFloat(percentInput.value) || 0;
                    priceInput.value = (importPrice * (1 + (percent / 100))).toFixed(2);
                });

                priceInput.addEventListener('input', () => {
                    const price = parseFloat(priceInput.value) || 0;
                    if (importPrice > 0) {
                        percentInput.value = ((price / importPrice - 1) * 100).toFixed(2);
                    }
                });
            }
        });
    </script>
</x-app-layout>
