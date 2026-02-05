<x-app-layout>
    @section('title', __('Постачальник'))
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Постачальник') }}
            </h2>
            <a href="{{ route('purchases.suppliers.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('Створити постачальника') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('purchases.suppliers.index') }}" class="flex flex-wrap gap-3 items-end">
                        <div class="flex-1 min-w-[200px]">
                            <x-input-label for="search" :value="__('Шукати по назві або коду постачальника')" />
                            <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" value="{{ $filters['search'] }}" />
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <x-input-label for="category" :value="__('Категорія')" />
                            <select id="category" name="category" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">{{ __('Усі') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected($filters['category'] === $category)>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pt-6">
                            <x-primary-button>{{ __('Застосувати') }}</x-primary-button>
                        </div>
                    </form>

                    @if (session('status'))
                        <div class="mt-4 text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Код</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Назва</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Коротка назва</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Кількість товарів</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Дата останнього імпорту</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Дія</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($suppliers as $supplier)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $supplier->code }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $supplier->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $supplier->short_name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $supplier->purchase_items_count }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            {{ optional($supplier->purchases_max_imported_at)->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            <div class="flex items-center gap-3">
                                                <a href="{{ route('purchases.suppliers.show', $supplier) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    {{ __('Коригувати') }}
                                                </a>
                                                <form method="POST" action="{{ route('purchases.suppliers.toggle', $supplier) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900">
                                                        {{ $supplier->is_active ? __('Деактивувати') : __('Activate') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('No suppliers found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $suppliers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
