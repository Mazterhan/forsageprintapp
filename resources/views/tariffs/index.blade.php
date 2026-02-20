<x-app-layout>
    @section('title', $title)
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $title }}
            </h2>
            <a href="{{ route('tariffs.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
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
                    <form method="GET" action="{{ route('tariffs.index') }}" class="w-full flex flex-wrap items-end gap-4">
                        <div style="width: 400px;">
                            <label class="block font-medium text-sm text-gray-700" for="search">Пошук за назвою</label>
                            <input id="search" name="search" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ $filters['search'] }}" />
                        </div>
                        <div class="flex-1 min-w-[180px]" x-data="{ open: false }">
                            <label class="block font-medium text-sm text-gray-700">Показати додаткові ціни</label>
                            <div class="relative mt-1">
                                <button type="button" @click="open = !open" class="block w-full border border-gray-300 rounded-md shadow-sm bg-white text-sm text-gray-700 text-left px-3 py-2 pr-8">
                                    {{ __('Вибрати ціни') }}
                                </button>
                                <svg class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                                <div x-show="open" @click.outside="open = false" class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-sm max-h-60 overflow-y-auto">
                                    @foreach ($extraPriceOptions as $key => $label)
                                        <label class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700">
                                            <input type="checkbox" name="extra_prices[]" value="{{ $key }}" class="rounded border-gray-300" @checked(in_array($key, $selectedExtraPrices, true))>
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label class="block font-medium text-sm text-gray-700" for="category">Категорія</label>
                            <select id="category" name="category" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                                <option value="">{{ __('Все') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected($filters['category'] === $category)>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label class="block font-medium text-sm text-gray-700" for="product_group_id">Внутрішня назва товару</label>
                            <select id="product_group_id" name="product_group_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                                <option value="">{{ __('Все') }}</option>
                                @foreach ($productGroups as $group)
                                    <option value="{{ $group->id }}" @selected((string) ($filters['product_group_id'] ?? '') === (string) $group->id)>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="width: 100px;">
                            <label class="block font-medium text-sm text-gray-700" for="price_from">Ціна від</label>
                            <input id="price_from" name="price_from" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ $filters['price_from'] }}" />
                        </div>
                        <div style="width: 100px;">
                            <label class="block font-medium text-sm text-gray-700" for="price_to">Ціна до</label>
                            <input id="price_to" name="price_to" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ $filters['price_to'] }}" />
                        </div>
                        <div class="ml-auto pt-6">
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
                                            $nextDirection = $currentSort === 'internal_code' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('tariffs.index', array_merge(request()->query(), ['sort' => 'internal_code', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                            Внутрішній код
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
                                        <a href="{{ route('tariffs.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                            Назва товару
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
                                        <a href="{{ route('tariffs.index', array_merge(request()->query(), ['sort' => 'category', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                            категорія товару
                                            @if ($currentSort === 'category')
                                                <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $nextDirection = $currentSort === 'product_group' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('tariffs.index', array_merge(request()->query(), ['sort' => 'product_group', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                            Внутрішня назва товару
                                            @if ($currentSort === 'product_group')
                                                <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $nextDirection = $currentSort === 'sale_price' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('tariffs.index', array_merge(request()->query(), ['sort' => 'sale_price', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1 justify-center">
                                            Роздрібна ціна
                                            @if ($currentSort === 'sale_price')
                                                <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    @foreach ($selectedExtraPrices as $extraKey)
                                        @php
                                            $extraLabel = $extraPriceOptions[$extraKey] ?? $extraKey;
                                            $sortKey = $extraKey === 'wholesale' ? 'wholesale_price' : ($extraKey === 'urgent' ? 'urgent_price' : $extraKey);
                                            $nextDirection = $currentSort === $sortKey && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                            <a href="{{ route('tariffs.index', array_merge(request()->query(), ['sort' => $sortKey, 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1 justify-center">
                                                {{ $extraLabel }}
                                                @if ($currentSort === $sortKey)
                                                    <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                                @else
                                                    <span class="text-gray-400">↕</span>
                                                @endif
                                            </a>
                                        </th>
                                    @endforeach
                                    @if (Auth::user()->role === 'admin' || Auth::user()->role === 'manager')
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Дія</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($tariffs as $tariff)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $tariff->internal_code }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            <a href="{{ route('tariffs.show', $tariff) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $tariff->name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ $tariff->category }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $tariff->productGroup?->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700 text-center">{{ number_format((float) $tariff->sale_price, 2, '.', '') }}</td>
                                        @foreach ($selectedExtraPrices as $extraKey)
                                            @php
                                                $extraValue = $extraKey === 'wholesale'
                                                    ? $tariff->wholesale_price
                                                    : ($extraKey === 'urgent' ? $tariff->urgent_price : null);
                                            @endphp
                                            <td class="px-4 py-2 text-sm text-gray-700 text-center">
                                                {{ $extraValue !== null ? number_format((float) $extraValue, 2, '.', '') : '' }}
                                            </td>
                                        @endforeach
                                        @if (Auth::user()->role === 'admin' || Auth::user()->role === 'manager')
                                            <td class="px-4 py-2 text-sm text-gray-700 text-center">
                                                <form method="POST" action="{{ route('tariffs.deactivate', $tariff) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-gray-600 hover:text-gray-900">
                                                        {{ __('Деактивувати') }}
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('No tariffs found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $tariffs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
