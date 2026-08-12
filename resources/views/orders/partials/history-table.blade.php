@php
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
                    'Опис: '.(($value['description'] ?? '') ?: '—'),
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
@endphp

@if($histories->isEmpty())
    <div class="px-4 py-8 text-center text-sm text-gray-500">
        Історія змін замовлення відсутня.
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full table-fixed border-b border-gray-200 text-sm">
            <thead style="background-color: #D3D4D4;">
                <tr>
                    <th class="w-[150px] border px-3 py-2 text-left">Час</th>
                    <th class="w-[180px] border px-3 py-2 text-left">Користувач</th>
                    <th class="w-[180px] border px-3 py-2 text-left">Тип операції</th>
                    <th class="w-[220px] border px-3 py-2 text-left">Що змінено</th>
                    <th class="border px-3 py-2 text-left">Було</th>
                    <th class="border px-3 py-2 text-left">Стало</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $previousHistoryTime = null;
                    $historyTimeGroupIndex = -1;
                @endphp
                @foreach($histories as $history)
                    @php
                        $historyTime = $formatOrderDate($history->created_at);
                        if ($historyTime !== $previousHistoryTime) {
                            $historyTimeGroupIndex++;
                            $previousHistoryTime = $historyTime;
                        }
                        $historyStripe = $historyTimeGroupIndex % 2;
                    @endphp
                    <tr
                        data-history-time="{{ $historyTime }}"
                        data-history-stripe="{{ $historyStripe }}"
                        class="{{ $historyStripe === 1 ? 'bg-slate-50' : 'bg-white' }} transition-colors hover:bg-cyan-50"
                    >
                        <td class="border px-3 py-2 align-top">{{ $historyTime }}</td>
                        <td class="border px-3 py-2 align-top">{{ $history->user?->name ?? '—' }}</td>
                        <td class="border px-3 py-2 align-top">{{ $operationLabels[$history->operation_type] ?? $history->operation_type }}</td>
                        <td class="border px-3 py-2 align-top">
                            {{ $history->description ?: ($history->field_name ?: 'Позиція') }}
                            @if($history->item_index)
                                <span class="text-gray-500">#{{ $history->item_index }}</span>
                            @endif
                        </td>
                        <td class="whitespace-pre-wrap break-words border px-3 py-2 align-top">{{ $formatHistoryValue($history->before_value) }}</td>
                        <td class="whitespace-pre-wrap break-words border px-3 py-2 align-top">{{ $formatHistoryValue($history->after_value) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
