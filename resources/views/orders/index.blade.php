<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Orders') }}
                </h2>
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md text-sm text-gray-700 cursor-not-allowed" disabled>
                    {{ __('Create order') }}
                </button>
            </div>
            <a href="{{ route('orders.clients.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('Clients') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full">
                <div class="p-6 text-gray-900 space-y-6">
                    <div class="text-sm text-gray-500">
                        {{ __('Filters will be added in the next iteration.') }}
                    </div>

                    <div class="text-sm text-gray-500">
                        {{ __('Orders table will be added in the next iteration.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
