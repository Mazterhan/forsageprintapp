<x-app-layout>
    @section('title', __('Замовлення :number', ['number' => $order->order_number]))

    @php
        $items = is_array($order->items) ? $order->items : [];
        $histories = $order->histories;
        $wasEdited = $histories->isNotEmpty();
        $lastAutomaticSaveAt = $histories->first()?->created_at;
        $formatOrderMoney = static function ($value): string {
            $formatted = number_format((float) $value, 2, '.', ' ');
            return preg_replace('/\.0+$/', '', $formatted) ?? $formatted;
        };
        $formatOrderDate = static fn ($date): string => $date
            ? $date->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i')
            : '—';
        $formatHistoryValue = static function ($value) use ($formatOrderMoney): string {
            if ($value === null || $value === '' || $value === []) {
                return '—';
            }

            if (is_array($value)) {
                if (array_keys($value) === ['value']) {
                    return $value['value'] === null || $value['value'] === '' ? '—' : (string) $value['value'];
                }

                if (array_key_exists('nomenclature', $value)) {
                    return implode("\n", [
                        'Номенклатура: '.($value['nomenclature'] ?: '—'),
                        'Кількість: '.$formatOrderMoney($value['quantity'] ?? 0),
                        'Вартість за одн.: '.$formatOrderMoney($value['unit_cost'] ?? 0),
                        'Сума: '.$formatOrderMoney($value['sum'] ?? 0),
                    ]);
                }

                return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—';
            }

            return (string) $value;
        };
        $operationLabels = [
            'item_created' => 'Створення',
            'item_updated' => 'Редагування',
            'item_deleted' => 'Видалення',
            'order_updated' => 'Редагування',
        ];
        if ((float) $order->amount_due <= 0 && (float) $order->total_cost > 0) {
            $paymentStatusLabel = 'Сплачено';
            $paymentStatusClass = 'border-green-300 bg-green-100 text-green-800';
        } elseif ((float) $order->payments_total > 0) {
            $paymentStatusLabel = 'Частково сплачено';
            $paymentStatusClass = 'border-amber-300 bg-amber-100 text-amber-800';
        } else {
            $paymentStatusLabel = 'Не сплачено';
            $paymentStatusClass = 'border-gray-300 bg-gray-100 text-gray-700';
        }
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <span class="absolute right-full top-1/2 mr-3 inline-flex -translate-y-1/2 whitespace-nowrap rounded-md border px-3 py-1 text-sm font-semibold {{ $paymentStatusClass }}">
                            {{ $paymentStatusLabel }}
                        </span>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ __('Замовлення :number', ['number' => $order->order_number]) }}
                        </h2>
                    </div>
                    <button
                        type="button"
                        title="Вивантажити замовлення у PDF"
                        aria-label="Вивантажити замовлення у PDF"
                        class="inline-flex h-[38px] w-[42px] items-center justify-center rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M8 15h8M8 18h5" />
                        </svg>
                    </button>
                    <button type="button" class="inline-flex h-[38px] items-center rounded-md border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Платежі
                    </button>
                </div>
                @if($wasEdited)
                    <div class="text-sm text-gray-700">
                        <span class="font-semibold">Час останнього автоматичного збереження:</span>
                        {{ $formatOrderDate($lastAutomaticSaveAt) }}
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    {{ __('Повернутись до замовлень') }}
                </a>
                <a href="{{ route('orders.edit', $order) }}" class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Редагувати
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[1700px] mx-auto space-y-4 px-6 sm:px-8 lg:px-12">
            <div class="bg-white shadow-sm sm:rounded-lg p-4 text-sm text-gray-800">
                <div class="flex flex-row items-start justify-between gap-6 w-full">
                    <div class="flex-1 min-w-0">
                        <span class="font-semibold">Замовник:</span>
                        {{ $order->client?->name ?: ($order->customer_name ?: '—') }}
                    </div>
                    <div class="space-y-2 text-left w-max shrink-0">
                        <div><span class="font-semibold">Дата створення:</span> {{ $formatOrderDate($order->created_at) }}</div>
                        <div><span class="font-semibold">Користувач:</span> {{ $order->lastEditedBy?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full table-fixed text-sm border-b border-gray-200">
                    <thead style="background-color: #D3D4D4;">
                        <tr>
                            <th class="w-[70px] px-3 py-2 border text-center">№</th>
                            <th class="px-3 py-2 border text-left">Номенклатура</th>
                            <th class="w-[150px] px-3 py-2 border text-right">Кількість</th>
                            <th class="w-[190px] px-3 py-2 border text-right">Вартість за одн.</th>
                            <th class="w-[180px] px-3 py-2 border text-right">Сума</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td class="px-3 py-2 border text-center align-middle font-semibold">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2 border align-top whitespace-pre-wrap break-words">{{ trim((string) ($item['nomenclature'] ?? '—')) }}</td>
                                <td class="px-3 py-2 border text-right align-middle">{{ $formatOrderMoney($item['quantity'] ?? 0) }}</td>
                                <td class="px-3 py-2 border text-right align-middle">{{ $formatOrderMoney($item['unit_cost'] ?? 0) }}</td>
                                <td class="px-3 py-2 border text-right align-middle font-semibold">{{ $formatOrderMoney($item['sum'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 border text-center text-gray-500">Позиції замовлення відсутні.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if(count($items) > 0)
                    <div class="px-4 py-4 border-t">
                        <div class="ml-auto grid w-fit grid-cols-[max-content_100px] items-center gap-x-3 gap-y-2 text-sm">
                            <div class="text-left font-semibold text-gray-700">Сума з ПДВ</div>
                            <div class="text-right font-semibold text-gray-900">{{ $formatOrderMoney($order->total_cost) }}</div>

                            <div class="text-left font-semibold text-gray-700">Загальна сума виплат</div>
                            <div class="text-right font-semibold text-gray-900">{{ $formatOrderMoney($order->payments_total) }}</div>

                            <div class="col-span-2 h-4" aria-hidden="true"></div>

                            <div class="text-left text-base font-bold text-gray-900">Сума до сплати</div>
                            <div class="text-right text-base font-bold text-gray-900">{{ $formatOrderMoney($order->amount_due) }}</div>
                        </div>
                    </div>
                @endif
            </div>

            @if($wasEdited)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 border-b font-semibold text-gray-800">
                        Історія змін замовлення
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-fixed text-sm border-b border-gray-200">
                            <thead style="background-color: #D3D4D4;">
                                <tr>
                                    <th class="w-[150px] px-3 py-2 border text-left">Час</th>
                                    <th class="w-[180px] px-3 py-2 border text-left">Користувач</th>
                                    <th class="w-[180px] px-3 py-2 border text-left">Тип операції</th>
                                    <th class="w-[220px] px-3 py-2 border text-left">Що змінено</th>
                                    <th class="px-3 py-2 border text-left">Було</th>
                                    <th class="px-3 py-2 border text-left">Стало</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($histories as $history)
                                    <tr>
                                        <td class="px-3 py-2 border align-top">{{ $formatOrderDate($history->created_at) }}</td>
                                        <td class="px-3 py-2 border align-top">{{ $history->user?->name ?? '—' }}</td>
                                        <td class="px-3 py-2 border align-top">{{ $operationLabels[$history->operation_type] ?? $history->operation_type }}</td>
                                        <td class="px-3 py-2 border align-top">
                                            {{ $history->description ?: ($history->field_name ?: 'Позиція') }}
                                            @if($history->item_index)
                                                <span class="text-gray-500">#{{ $history->item_index }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 border align-top whitespace-pre-wrap break-words">{{ $formatHistoryValue($history->before_value) }}</td>
                                        <td class="px-3 py-2 border align-top whitespace-pre-wrap break-words">{{ $formatHistoryValue($history->after_value) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
