<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $title }}
        </h2>
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
                            <label class="block font-medium text-sm text-gray-700">Підрядник</label>
                            <div class="relative mt-1">
                                <button type="button" @click="open = !open" class="block w-full border border-gray-300 rounded-md shadow-sm bg-white text-sm text-gray-700 text-left px-3 py-2 pr-8">
                                    {{ __('Вибрати підрядника') }}
                                </button>
                                <svg class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                                <div x-show="open" @click.outside="open = false" class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-sm max-h-60 overflow-y-auto">
                                    @foreach ($subcontractors as $subcontractor)
                                        <label class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700">
                                            <input type="checkbox" name="subcontractors[]" value="{{ $subcontractor->id }}" class="rounded border-gray-300" @checked(in_array($subcontractor->id, $filters['subcontractors'], true))>
                                            <span>{{ $subcontractor->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 min-w-[180px]" x-data="{ open: false }">
                            <label class="block font-medium text-sm text-gray-700">Показати ціну для категорії</label>
                            <div class="relative mt-1">
                                <button type="button" @click="open = !open" class="block w-full border border-gray-300 rounded-md shadow-sm bg-white text-sm text-gray-700 text-left px-3 py-2 pr-8">
                                    {{ __('Вибрати категорії') }}
                                </button>
                                <svg class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                                <div x-show="open" @click.outside="open = false" class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-sm max-h-60 overflow-y-auto">
                                    @forelse ($clientCategories as $clientCategory)
                                        <label class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700">
                                            <input type="checkbox" name="client_categories[]" value="{{ $clientCategory }}" class="rounded border-gray-300" @checked(in_array($clientCategory, $selectedClientCategories, true))>
                                            <span>{{ $clientCategory }}</span>
                                        </label>
                                    @empty
                                        <div class="px-3 py-2 text-sm text-gray-500">
                                            {{ __('No client category prices.') }}
                                        </div>
                                    @endforelse
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

                    <div class="mt-6 w-full overflow-x-auto">
                        <table class="min-w-full w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Внутрішній код</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Назва товару</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">категорія товару</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Роздрібна ціна</th>
                                    @foreach ($selectedClientCategories as $clientCategory)
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                            {{ __('Price.') }}{{ $clientCategory }}
                                        </th>
                                    @endforeach
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Підрядник</th>
                                    @if (Auth::user()->role === 'admin' || Auth::user()->role === 'manager')
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Дія</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($tariffs as $tariff)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $tariff->internal_code }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $tariff->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ $tariff->category }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700 text-center">{{ number_format((float) $tariff->sale_price, 2, '.', '') }}</td>
                                        @foreach ($selectedClientCategories as $clientCategory)
                                            @php
                                                $clientPrice = $tariff->clientPrices?->firstWhere('client_category', $clientCategory);
                                            @endphp
                                            <td class="px-4 py-2 text-sm text-gray-700 text-center">
                                                {{ $clientPrice?->price !== null ? number_format((float) $clientPrice->price, 2, '.', '') : '' }}
                                            </td>
                                        @endforeach
                                        <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ $tariff->subcontractor?->name }}</td>
                                        @if (Auth::user()->role === 'admin' || Auth::user()->role === 'manager')
                                            <td class="px-4 py-2 text-sm text-gray-700 text-center">
                                                <div class="flex items-center justify-center gap-3">
                                                    <a href="{{ route('tariffs.show', $tariff) }}" class="text-indigo-600 hover:text-indigo-900">
                                                        {{ __('Коригувати') }}
                                                    </a>
                                                    <form method="POST" action="{{ route('tariffs.deactivate', $tariff) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-gray-600 hover:text-gray-900">
                                                            {{ __('Деактивувати') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
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
