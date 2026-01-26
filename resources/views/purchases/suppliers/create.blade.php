<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Додати постачальника') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('purchases.suppliers.store') }}" class="space-y-8">
                        @csrf
                        @include('purchases.suppliers.partials.form', ['supplier' => null])
                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Створити') }}</x-primary-button>
                            <a href="{{ route('purchases.suppliers.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Відмінити') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
