<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Результат імпорту') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('warning'))
                <div class="text-sm text-amber-700 bg-amber-100 px-4 py-2 rounded">
                    {{ session('warning') }}
                </div>
            @endif
            @if (session('warnings'))
                <div class="text-sm text-amber-700 bg-amber-100 px-4 py-2 rounded">
                    <ul class="list-disc list-inside">
                        @foreach (session('warnings') as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <div class="text-gray-500">{{ __('Постачальник') }}</div>
                            <div class="font-medium">{{ $purchase->supplier?->name }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">{{ __('Файл') }}</div>
                            <div class="font-medium">{{ $purchase->original_filename }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">{{ __('Дата імпорту') }}</div>
                            <div class="font-medium">{{ optional($purchase->imported_at)->format('Y-m-d H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Імпортовані дані') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Внутрішній код</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Назва</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Одиниці</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Кількість</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ціна</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($items as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $item->internal_code }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $item->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $item->unit }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $item->qty }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ number_format((float) $item->price_vat, 2, '.', '') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $items->links() }}
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Помилки парсингу') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Рядок</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Повідомлення</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($errors as $error)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $error->row_number }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $error->message }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('No parsing errors.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
