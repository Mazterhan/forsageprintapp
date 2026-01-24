<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tariffs') }}
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
                    <form method="GET" action="{{ route('tariffs.index') }}" class="w-full flex flex-wrap gap-4 items-end">
                        <div style="width: 400px;">
                            <x-input-label for="search" :value="__('Search by name')" />
                            <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" value="{{ $filters['search'] }}" />
                        </div>
                        <div class="flex-[0.75] min-w-[180px]" x-data="{ open: false }">
                            <x-input-label :value="__('Subcontractors')" />
                            <div class="relative mt-1">
                                <button type="button" @click="open = !open" class="block w-full border border-gray-300 rounded-md shadow-sm bg-white text-sm text-gray-700 text-left px-3 py-2 pr-8">
                                    {{ __('Select subcontractors') }}
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
                        <div class="flex-[0.75] min-w-[180px]">
                            <x-input-label for="category" :value="__('Category')" />
                            <select id="category" name="category" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">{{ __('All') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected($filters['category'] === $category)>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="width: 100px;">
                            <x-input-label for="price_from" :value="__('Price from')" />
                            <x-text-input id="price_from" name="price_from" type="text" class="mt-1 block w-full" value="{{ $filters['price_from'] }}" />
                        </div>
                        <div style="width: 100px;">
                            <x-input-label for="price_to" :value="__('Price to')" />
                            <x-text-input id="price_to" name="price_to" type="text" class="mt-1 block w-full" value="{{ $filters['price_to'] }}" />
                        </div>
                        <div class="ml-auto" style="padding-top: 12px; padding-bottom: 12px;">
                            <x-primary-button>{{ __('Apply') }}</x-primary-button>
                        </div>
                    </form>

                    <div class="mt-6 w-full overflow-x-auto">
                        <table class="min-w-full w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Internal Code</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Price</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Subcontractor</th>
                                    @if (Auth::user()->role === 'admin' || Auth::user()->role === 'manager')
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
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
                                        <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ $tariff->subcontractor?->name }}</td>
                                        @if (Auth::user()->role === 'admin' || Auth::user()->role === 'manager')
                                            <td class="px-4 py-2 text-sm text-gray-700 text-center">
                                                <div class="flex items-center justify-center gap-3">
                                                    <a href="{{ route('tariffs.show', $tariff) }}" class="text-indigo-600 hover:text-indigo-900">
                                                        {{ __('Edit') }}
                                                    </a>
                                                    <form method="POST" action="{{ route('tariffs.deactivate', $tariff) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-gray-600 hover:text-gray-900">
                                                            {{ __('Deactivate') }}
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
