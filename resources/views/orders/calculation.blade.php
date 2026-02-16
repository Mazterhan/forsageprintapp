<x-app-layout>
    @section('title', __('Прорахунок замовлення'))
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Прорахунок замовлення') }}
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    {{ __('Повернутись до замовлень') }}
                </a>
                <a href="{{ route('orders.proposals') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    {{ __('Повернутись до заявок') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full">
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-500">
                        {{ __('Сторінка прорахунку замовлення підготовлена. Детальна логіка буде додана на наступних ітераціях.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
