<x-app-layout>
    @section('title', __('Заявка :number', ['number' => $proposal->proposal_number]))
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Заявка :number', ['number' => $proposal->proposal_number]) }}
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('orders.proposals') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Повернутись до заявок</a>
                <a href="{{ route('orders.calculation', ['proposal' => $proposal->id]) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md text-sm text-white hover:bg-gray-700">Редагувати</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-4 text-sm text-gray-800">
                <div class="flex flex-row items-start justify-between gap-6 w-full">
                    <div class="space-y-2 flex-1 min-w-0">
                        <div><span class="font-semibold">Замовник:</span> {{ ($state['client_name'] ?? $proposal->client_name) ?: '—' }}</div>
                        <div><span class="font-semibold">Коефіцієнт терміновості:</span> {{ $state['urgency_coefficient'] ?? '1.00' }}</div>
                    </div>
                    <div class="space-y-2 text-left w-max shrink-0">
                        <div><span class="font-semibold">Дата створення:</span> {{ optional($proposal->created_at)->format('d.m.Y H:i') }}</div>
                        @if(((int) ($proposal->corrections_count ?? 0)) >= 1)
                            <div><span class="font-semibold">Останнє коригування:</span> {{ optional($proposal->updated_at)->format('d.m.Y H:i') }}</div>
                            <div><span class="font-semibold">Кількість коригувань:</span> {{ (int) ($proposal->corrections_count ?? 0) }}</div>
                        @endif
                        <div><span class="font-semibold">Користувач:</span> {{ $proposal->user?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            @php
                $hasMultipleProducts = count($products) > 1;
            @endphp

            @if($hasMultipleProducts)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 border-b font-semibold text-gray-800">
                        Типи виробу (об'єднана таблиця)
                    </div>
                    <table class="min-w-full text-sm border-b border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 border text-left">Виріб №</th>
                                <th class="px-3 py-2 border text-left">Тип виробу</th>
                                <th class="px-3 py-2 border text-left">Позиція замовлення</th>
                                <th class="px-3 py-2 border text-left">Матеріал</th>
                                <th class="px-3 py-2 border text-left">Товщина</th>
                                <th class="px-3 py-2 border text-left">Ширина(м)</th>
                                <th class="px-3 py-2 border text-left">Висота(м)</th>
                                <th class="px-3 py-2 border text-left">Кількісь(шт)</th>
                                <th class="px-3 py-2 border text-left">Шари друку (шт):CMYK</th>
                                <th class="px-3 py-2 border text-left">Шари друку (шт):Білий</th>
                                <th class="px-3 py-2 border text-right">Ціна</th>
                                <th class="px-3 py-2 border text-right">Вартість виробу</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                @php
                                    $positions = is_array($product['positions'] ?? null) ? $product['positions'] : [];
                                    $isUv = mb_strtolower((string)($product['productTypeName'] ?? ''), 'UTF-8') === mb_strtolower('УФ друк', 'UTF-8');
                                    $isSheet = (string)($product['materialType'] ?? '') === 'Листовий';
                                    $productTotalCost = (float)($product['total_cost'] ?? 0);
                                @endphp
                                @if(count($positions) === 0)
                                    <tr>
                                        <td class="px-3 py-2 border">{{ $product['index'] ?? 'Н/Д' }}</td>
                                        <td class="px-3 py-2 border">{{ ($product['productTypeName'] ?? '') !== '' ? $product['productTypeName'] : 'Н/Д' }}</td>
                                        <td class="px-3 py-2 border">Н/Д</td>
                                        <td class="px-3 py-2 border">{{ ($product['material'] ?? '') !== '' ? $product['material'] : 'Н/Д' }}</td>
                                        <td class="px-3 py-2 border">{{ $isSheet ? (($product['manualThickness'] ?? '') !== '' ? $product['manualThickness'] : (($product['thickness'] ?? '') !== '' ? $product['thickness'] : 'Н/Д')) : 'Н/Д' }}</td>
                                        <td class="px-3 py-2 border">Н/Д</td>
                                        <td class="px-3 py-2 border">Н/Д</td>
                                        <td class="px-3 py-2 border">Н/Д</td>
                                        <td class="px-3 py-2 border">Н/Д</td>
                                        <td class="px-3 py-2 border">Н/Д</td>
                                        <td class="px-3 py-2 border text-right">Н/Д</td>
                                        <td class="px-3 py-2 border text-right font-semibold">{{ number_format($productTotalCost, 2, '.', ' ') }}</td>
                                    </tr>
                                @else
                                    @foreach($positions as $idx => $position)
                                        @php
                                            $hasPositionCost = array_key_exists('cost', $position) && $position['cost'] !== null && $position['cost'] !== '';
                                            $positionCost = $hasPositionCost ? (float)$position['cost'] : null;
                                            $isZeroPriceRow = $hasPositionCost && abs($positionCost) < 0.000001;
                                            $isZeroLike = static function ($value): bool {
                                                if ($value === null || $value === '') {
                                                    return false;
                                                }
                                                if (is_string($value)) {
                                                    $trimmed = trim($value);
                                                    if ($trimmed === '' || $trimmed === '—' || mb_strtoupper($trimmed, 'UTF-8') === 'Н/Д') {
                                                        return false;
                                                    }
                                                    $normalized = str_replace(',', '.', $trimmed);
                                                    if (!is_numeric($normalized)) {
                                                        return false;
                                                    }
                                                    return abs((float)$normalized) < 0.000001;
                                                }

                                                if (!is_numeric($value)) {
                                                    return false;
                                                }

                                                return abs((float)$value) < 0.000001;
                                            };
                                            $cellStyle = static function ($value) use ($isZeroPriceRow, $isZeroLike): string {
                                                return ($isZeroPriceRow && $isZeroLike($value)) ? 'background-color:#F01326;color:#fff;' : '';
                                            };
                                            $materialValue = ($product['material'] ?? '') !== '' ? $product['material'] : '—';
                                            $thicknessValue = $isSheet
                                                ? (($product['manualThickness'] ?? '') !== '' ? $product['manualThickness'] : (($product['thickness'] ?? '') !== '' ? $product['thickness'] : '—'))
                                                : 'Н/Д';
                                            $widthValue = ($position['width'] ?? '') !== '' ? $position['width'] : '—';
                                            $heightValue = ($position['height'] ?? '') !== '' ? $position['height'] : '—';
                                            $qtyValue = ($position['qty'] ?? '') !== '' ? $position['qty'] : '—';
                                            $cmykValue = $isUv ? (((int)($position['cmyk'] ?? 0) > 0) ? ($position['cmyk'] ?? 0) : '—') : 'Н/Д';
                                            $whiteValue = $isUv ? (((int)($position['white'] ?? 0) > 0) ? ($position['white'] ?? 0) : '—') : 'Н/Д';
                                            $costValue = $hasPositionCost ? number_format((float)$position['cost'], 2, '.', ' ') : 'Н/Д';
                                        @endphp
                                        <tr>
                                            @if($idx === 0)
                                                <td class="px-3 py-2 border align-top" rowspan="{{ max(count($positions), 1) }}">{{ $product['index'] ?? 'Н/Д' }}</td>
                                                <td class="px-3 py-2 border align-top" rowspan="{{ max(count($positions), 1) }}">{{ ($product['productTypeName'] ?? '') !== '' ? $product['productTypeName'] : 'Н/Д' }}</td>
                                            @endif
                                            <td class="px-3 py-2 border">#{{ $position['index'] ?? ($idx + 1) }}</td>
                                            <td class="px-3 py-2 border" style="{{ $cellStyle($materialValue) }}">{{ $materialValue }}</td>
                                            <td class="px-3 py-2 border" style="{{ $cellStyle($thicknessValue) }}">{{ $thicknessValue }}</td>
                                            <td class="px-3 py-2 border" style="{{ $cellStyle($widthValue) }}">{{ $widthValue }}</td>
                                            <td class="px-3 py-2 border" style="{{ $cellStyle($heightValue) }}">{{ $heightValue }}</td>
                                            <td class="px-3 py-2 border" style="{{ $cellStyle($qtyValue) }}">{{ $qtyValue }}</td>
                                            <td class="px-3 py-2 border" style="{{ $cellStyle($cmykValue) }}">{{ $cmykValue }}</td>
                                            <td class="px-3 py-2 border" style="{{ $cellStyle($whiteValue) }}">{{ $whiteValue }}</td>
                                            <td class="px-3 py-2 border text-right" style="{{ $cellStyle($costValue) }}">{{ $costValue }}</td>
                                            @if($idx === 0)
                                                <td class="px-3 py-2 border text-right align-top font-semibold" rowspan="{{ max(count($positions), 1) }}">
                                                    {{ number_format($productTotalCost, 2, '.', ' ') }}
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @forelse($products as $product)
                @php
                    $positions = is_array($product['positions'] ?? null) ? $product['positions'] : [];
                    $serviceRows = is_array($product['service_rows'] ?? null) ? $product['service_rows'] : [];
                    $showServices = count($positions) === 1;
                    $isUv = mb_strtolower((string)($product['productTypeName'] ?? ''), 'UTF-8') === mb_strtolower('УФ друк', 'UTF-8');
                    $showThickness = (string)($product['materialType'] ?? '') === 'Листовий';
                @endphp

                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    @unless($hasMultipleProducts)
                        <div class="px-4 py-3 bg-gray-50 border-b font-semibold text-gray-800">
                            Тип виробу #{{ $product['index'] ?? 1 }}: {{ $product['productTypeName'] ?? '—' }}
                        </div>

                        <table class="min-w-full text-sm border-b border-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 border text-left">Тип виробу</th>
                                    @if(count($positions) > 1)
                                        <th class="px-3 py-2 border text-left">Позиція замовлення</th>
                                    @endif
                                    <th class="px-3 py-2 border text-left">Матеріал</th>
                                    @if($showThickness)
                                        <th class="px-3 py-2 border text-left">Товщина</th>
                                    @endif
                                    <th class="px-3 py-2 border text-left">Ширина(м)</th>
                                    <th class="px-3 py-2 border text-left">Висота(м)</th>
                                    <th class="px-3 py-2 border text-left">Кількісь(шт)</th>
                                    @if($isUv)
                                        <th class="px-3 py-2 border text-left">Шари друку (шт):CMYK</th>
                                        <th class="px-3 py-2 border text-left">Шари друку (шт):Білий</th>
                                    @endif
                                    <th class="px-3 py-2 border text-right">Ціна</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($positions as $idx => $position)
                                    <tr>
                                        @if($idx === 0)
                                            <td class="px-3 py-2 border align-top" rowspan="{{ max(count($positions), 1) }}">{{ $product['productTypeName'] ?? '—' }}</td>
                                        @endif
                                        @if(count($positions) > 1)
                                            <td class="px-3 py-2 border">#{{ $position['index'] ?? ($idx + 1) }}</td>
                                        @endif
                                        <td class="px-3 py-2 border">{{ $product['material'] ?? '—' }}</td>
                                        @if($showThickness)
                                            <td class="px-3 py-2 border">{{ ($product['manualThickness'] ?? '') !== '' ? $product['manualThickness'] : ($product['thickness'] ?? '—') }}</td>
                                        @endif
                                        <td class="px-3 py-2 border">{{ ($position['width'] ?? '') !== '' ? $position['width'] : '—' }}</td>
                                        <td class="px-3 py-2 border">{{ ($position['height'] ?? '') !== '' ? $position['height'] : '—' }}</td>
                                        <td class="px-3 py-2 border">{{ ($position['qty'] ?? '') !== '' ? $position['qty'] : '—' }}</td>
                                        @if($isUv)
                                            <td class="px-3 py-2 border">{{ ((int)($position['cmyk'] ?? 0) > 0) ? ($position['cmyk'] ?? 0) : '—' }}</td>
                                            <td class="px-3 py-2 border">{{ ((int)($position['white'] ?? 0) > 0) ? ($position['white'] ?? 0) : '—' }}</td>
                                        @endif
                                        <td class="px-3 py-2 border text-right">{{ number_format((float)($position['cost'] ?? 0), 2, '.', ' ') }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-3 py-2 border text-gray-500" colspan="{{ $isUv ? 10 : 8 }}">Позиції відсутні.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endunless

                    @if($showServices)
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 border text-left">Послуги до виробу</th>
                                    <th class="px-3 py-2 border text-left">Назва послуги</th>
                                    <th class="px-3 py-2 border text-left">Опис</th>
                                    <th class="px-3 py-2 border text-right">Ціна</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($serviceRows as $sIndex => $row)
                                    <tr>
                                        @if($sIndex === 0)
                                            <td class="px-3 py-2 border align-top" rowspan="{{ max(count($serviceRows), 1) }}">Послуги до виробу</td>
                                        @endif
                                        <td class="px-3 py-2 border">
                                            {{ $row['name'] ?? '—' }}
                                            @if(($row['key'] ?? '') === 'rolling' && !empty($row['rolling_individual']))
                                                <strong>(Індивідуально)</strong>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 border">
                                            @if(($row['key'] ?? '') === 'rolling' && is_array($row['rolling_meta'] ?? null))
                                                @php($meta = $row['rolling_meta'])
                                                <table class="w-full text-xs border border-gray-200">
                                                    <tbody>
                                                        @if(!empty($row['rolling_individual']))
                                                            <tr>
                                                                <td class="px-2 py-1 border font-semibold">Матеріал індивідуальної прикатки 1</td>
                                                                <td class="px-2 py-1 border">{{ $meta['ip1_material'] ?? '-' }}</td>
                                                                <td class="px-2 py-1 border">Ширина(м): {{ $meta['ip1_width'] ?? '0' }}, Висота(м): {{ $meta['ip1_height'] ?? '0' }}, Кількість(шт): {{ $meta['ip1_qty'] ?? '0' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="px-2 py-1 border font-semibold">Матеріал індивідуальної прикатки 2</td>
                                                                <td class="px-2 py-1 border">{{ $meta['ip2_material'] ?? '-' }}</td>
                                                                <td class="px-2 py-1 border">Ширина(м): {{ $meta['ip2_width'] ?? '0' }}, Висота(м): {{ $meta['ip2_height'] ?? '0' }}, Кількість(шт): {{ $meta['ip2_qty'] ?? '0' }}</td>
                                                            </tr>
                                                        @else
                                                            <tr>
                                                                <td class="px-2 py-1 border font-semibold">Матеріал прикатки 1</td>
                                                                <td class="px-2 py-1 border">{{ $meta['p1_material'] ?? '-' }}</td>
                                                                <td class="px-2 py-1 border">Ширина(м): {{ $meta['width'] ?? '0' }}, Висота(м): {{ $meta['height'] ?? '0' }}, Кількість(шт): {{ $meta['qty'] ?? '0' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="px-2 py-1 border font-semibold">Матеріал прикатки 2</td>
                                                                <td class="px-2 py-1 border">{{ $meta['p2_material'] ?? '-' }}</td>
                                                                <td class="px-2 py-1 border">Ширина(м): {{ $meta['width'] ?? '0' }}, Висота(м): {{ $meta['height'] ?? '0' }}, Кількість(шт): {{ $meta['qty'] ?? '0' }}</td>
                                                            </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            @else
                                                <div class="whitespace-pre-line">{{ $row['description'] ?? '—' }}</div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 border text-right">{{ number_format((float)($row['cost'] ?? 0), 2, '.', ' ') }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-3 py-2 border text-gray-500" colspan="4">Послуги не застосовані.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif

                    <div class="px-4 py-3 border-t text-right text-sm">
                        <span class="font-semibold">Вартість виробу:</span>
                        <span class="font-bold">{{ number_format((float)($product['total_cost'] ?? 0), 2, '.', ' ') }}</span>
                    </div>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-500">У заявці немає збережених блоків виробів.</div>
            @endforelse

            <div class="bg-white shadow-sm sm:rounded-lg p-4 text-right">
                <span class="font-bold text-lg">Всього: {{ number_format((float)($summary['order_total'] ?? $proposal->total_cost ?? 0), 2, '.', ' ') }}</span>
            </div>
        </div>
    </div>
</x-app-layout>
