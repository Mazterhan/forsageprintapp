<x-app-layout>
    @section('title', __('Постачальники. Імпорт товарів'))
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Постачальники. Імпорт товарів') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('pricing.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    {{ __('Ціноутворення') }}
                </a>
                <a href="{{ route('purchases.suppliers.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    {{ __('Постачальники') }}
                </a>
                <a href="{{ route('purchases.import.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    {{ __('Імпорт даних') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('status'))
                        <div class="mb-4 text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                            {{ session('status') }}
                        </div>
                    @endif
                    <form method="GET" action="{{ route('purchases.index') }}" class="flex flex-wrap items-end gap-4">
                        <div class="flex-1 min-w-[220px]">
                            <label class="block font-medium text-sm text-gray-700" for="item_search">Пошук за внутрішнім кодом, назвою або кодом рахунку-фактури</label>
                            <input class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" id="item_search" name="item_search" type="text" value="{{ $filters['item_search'] }}">
                        </div>
                        <div class="flex-1 min-w-[200px]" x-data="{ open: false }">
                            <label class="block font-medium text-sm text-gray-700">Постачальники</label>
                            <div class="relative mt-1">
                                <button type="button" @click="open = !open" class="block w-full border border-gray-300 rounded-md shadow-sm bg-white text-sm text-gray-700 text-left px-3 py-2 pr-8">
                                    {{ __('Оберіть постачальника') }}
                                </button>
                                <svg class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                                <div x-show="open" @click.outside="open = false" class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-sm max-h-60 overflow-y-auto">
                                    @foreach ($suppliers as $supplier)
                                        <label class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700">
                                            <input type="checkbox" name="suppliers[]" value="{{ $supplier->id }}" class="rounded border-gray-300" @checked(in_array($supplier->id, $filters['suppliers'], true))>
                                            <span>{{ $supplier->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label class="block font-medium text-sm text-gray-700" for="category">Категорія товару</label>
                            <select id="category" name="category" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                                <option value="">{{ __('Всі') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected($filters['category'] === $category)>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pt-6">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Застосувати
                            </button>
                        </div>
                    </form>

                    @php
                        $currentSort = request('sort');
                        $currentDirection = request('direction', 'asc');
                    @endphp
                    <div class="mt-6 w-full overflow-x-auto">
                        <table class="min-w-full w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $nextDirection = $currentSort === 'supplier' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('purchases.index', array_merge(request()->query(), ['sort' => 'supplier', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                            Постачальник
                                            @if ($currentSort === 'supplier')
                                                <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $nextDirection = $currentSort === 'external_code' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('purchases.index', array_merge(request()->query(), ['sort' => 'external_code', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                            Код товару (постачальника)
                                            @if ($currentSort === 'external_code')
                                                <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $nextDirection = $currentSort === 'internal_code' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('purchases.index', array_merge(request()->query(), ['sort' => 'internal_code', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                            Код товару
                                            @if ($currentSort === 'internal_code')
                                                <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $nextDirection = $currentSort === 'name' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('purchases.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                            Назва
                                            @if ($currentSort === 'name')
                                                <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $nextDirection = $currentSort === 'price' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('purchases.index', array_merge(request()->query(), ['sort' => 'price', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1 justify-center">
                                            Закупівельна ціна
                                            @if ($currentSort === 'price')
                                                <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $nextDirection = $currentSort === 'imported_at' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('purchases.index', array_merge(request()->query(), ['sort' => 'imported_at', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                            Дата імпорту
                                            @if ($currentSort === 'imported_at')
                                                <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Файл</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($items as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $item->supplier?->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $item->external_code }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $item->internal_code }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $item->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700 text-center">{{ $item->price_vat !== null ? number_format((float) $item->price_vat, 2, '.', '') : '' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ optional($item->imported_at)->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $item->purchase?->original_filename }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('No imports found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $items->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
