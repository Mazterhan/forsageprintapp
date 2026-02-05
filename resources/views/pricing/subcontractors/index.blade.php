<x-app-layout>
    @section('title', __('Підрядні організації'))
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Підрядні організації') }}
            </h2>
            <a href="{{ route('pricing.subcontractors.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('Додати підрядну організацію') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('pricing.subcontractors.index') }}" class="flex flex-wrap gap-3 items-end">
                        <div class="flex-1 min-w-[240px]">
                            <x-input-label for="search" :value="__('Пошук по назві')" />
                            <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" value="{{ $filters['search'] }}" />
                        </div>
                        <div class="pt-6">
                            <x-primary-button>{{ __('Застосувати') }}</x-primary-button>
                        </div>
                    </form>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Назва</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Категорія</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Дія</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($subcontractors as $subcontractor)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $subcontractor->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $subcontractor->category }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            {{ $subcontractor->is_active ? __('Active') : __('Inactive') }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            <div class="flex items-center gap-3">
                                                <a href="{{ route('pricing.subcontractors.edit', $subcontractor) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    {{ __('Коригувати') }}
                                                </a>
                                                <form method="POST" action="{{ route('pricing.subcontractors.toggle', $subcontractor) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-gray-600 hover:text-gray-900">
                                                        {{ $subcontractor->is_active ? __('Деактивувати') : __('Activate') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('No subcontractors found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $subcontractors->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
