<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Імпорт закупівель') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('purchases.import.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="supplier_id" :value="__('Постачальник')" />
                            <select id="supplier_id" name="supplier_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">{{ __('Виберіть постачальника') }}</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('supplier_id')" />
                        </div>

                        <div>
                            <x-input-label for="supplier_name" :value="__('Якщо постачальника немає у списку - впишіть його вручну')" />
                            <x-text-input id="supplier_name" name="supplier_name" type="text" class="mt-1 block w-full" value="{{ old('supplier_name') }}" />
                            <x-input-error class="mt-2" :messages="$errors->get('supplier_name')" />
                        </div>

                        <div>
                            <x-input-label for="file" :value="__('File (CSV або XLSX)')" />
                            <input id="file" name="file" type="file" class="mt-1 block w-full text-sm text-gray-700" required />
                            <x-input-error class="mt-2" :messages="$errors->get('file')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Імпорт') }}</x-primary-button>
                            <a href="{{ route('purchases.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Повернутись') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
