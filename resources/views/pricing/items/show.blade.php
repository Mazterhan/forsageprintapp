<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Цінник') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('pricing.items.update', $item) }}" class="space-y-6" data-import-price="{{ $item->import_price ?? 0 }}">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="internal_code" :value="__('Внутрішній код')" />
                            <x-text-input id="internal_code" type="text" class="mt-1 block w-full" value="{{ $item->internal_code }}" disabled />
                        </div>

                        <div>
                            <x-input-label for="name" :value="__('Назва товару')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $item->name) }}" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="category" :value="__('Категорія')" />
                            <x-text-input id="category" name="category" type="text" class="mt-1 block w-full" value="{{ old('category', $item->category) }}" />
                        </div>

                        <div>
                            <x-input-label for="subcontractor_id" :value="__('Субпідрядник')" />
                            <select id="subcontractor_id" name="subcontractor_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">{{ __('Обрати') }}</option>
                                @foreach ($subcontractors as $subcontractor)
                                    <option value="{{ $subcontractor->id }}" @selected($item->subcontractor_id === $subcontractor->id)>
                                        {{ $subcontractor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="import_price" :value="__('Закупівельна ціна')" />
                                <x-text-input id="import_price" type="text" class="mt-1 block w-full" value="{{ old('import_price', $item->import_price) }}" disabled />
                            </div>
                            <div>
                                <x-input-label for="markup_percent" :value="__('Націнка %')" />
                                <x-text-input id="markup_percent" name="markup_percent" type="text" class="mt-1 block w-full markup-percent" value="{{ old('markup_percent', $item->markup_percent) }}" />
                            </div>
                            <div>
                                <x-input-label for="markup_price" :value="__('Роздрібна ціна')" />
                                <x-text-input id="markup_price" name="markup_price" type="text" class="mt-1 block w-full markup-price" value="{{ old('markup_price', $item->markup_price) }}" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Застосувати') }}</x-primary-button>
                            <a href="{{ route('pricing.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Відмінити') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Історія цін') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Дата</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Закупівельна ціна</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Націнка %</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Роздрібна ціна</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Користувач</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($history as $row)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ optional($row->changed_at)->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->import_price }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->markup_percent }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->markup_price }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $row->user?->name }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('No history yet.') }}
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

    <script>
        (function () {
            const form = document.querySelector('form[data-import-price]');
            if (!form) return;

            const importPrice = parseFloat(form.getAttribute('data-import-price')) || 0;
            const percentInput = form.querySelector('.markup-percent');
            const priceInput = form.querySelector('.markup-price');

            if (!percentInput || !priceInput) return;

            percentInput.addEventListener('input', () => {
                const percent = parseFloat(percentInput.value) || 0;
                priceInput.value = (importPrice * (1 + (percent / 100))).toFixed(2);
            });

            priceInput.addEventListener('input', () => {
                const price = parseFloat(priceInput.value) || 0;
                if (importPrice > 0) {
                    percentInput.value = ((price / importPrice - 1) * 100).toFixed(2);
                }
            });
        })();
    </script>
</x-app-layout>
