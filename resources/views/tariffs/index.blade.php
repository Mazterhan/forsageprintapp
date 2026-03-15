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
                                        <td colspan="{{ (Auth::user()->role === 'admin' || Auth::user()->role === 'manager') ? 6 : 5 }}" class="px-4 py-6 text-center text-sm text-gray-500">
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
