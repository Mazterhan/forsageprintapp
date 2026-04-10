<x-app-layout>
    @section('title', __('Заявка :number', ['number' => $proposal->proposal_number]))
    @php
        $hasMultipleProducts = count($products ?? []) > 1;
        $urgencyCoefficientDisplay = $state['urgency_coefficient']
            ?? $state['urgencyCoefficient']
            ?? data_get($state, 'summary.urgency_coefficient')
            ?? '1.00';
        $summaryOrderTotal = (float) ($summary['order_total'] ?? $proposal->total_cost ?? 0);
        $productsTotalRaw = collect($products ?? [])->sum(function ($product) {
            if (array_key_exists('total_cost', $product) && $product['total_cost'] !== null && $product['total_cost'] !== '') {
                return (float) $product['total_cost'];
            }

            return (float) ($product['positions_cost'] ?? 0) + (float) ($product['services_cost'] ?? 0);
        });
        $minimumOrderApplied = array_key_exists('minimum_order_applied', $summary ?? [])
            ? filter_var($summary['minimum_order_applied'], FILTER_VALIDATE_BOOLEAN)
            : false;
        if (!$minimumOrderApplied) {
            $minimumOrderApplied = abs($summaryOrderTotal - 100.0) < 0.000001 && $productsTotalRaw < 100;
        }
        $requestedViewMode = request('view_mode');
        $viewMode = in_array($requestedViewMode, ['combined', 'grouped', 'combined_services'], true)
            ? $requestedViewMode
            : 'combined';
        if (!$hasMultipleProducts) {
            $viewMode = 'combined';
        }
    @endphp
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Заявка :number', ['number' => $proposal->proposal_number]) }}
                </h2>
                @if($hasMultipleProducts)
                    <select
                        onchange="window.location.href='{{ route('orders.proposals.show', $proposal) }}?view_mode=' + this.value"
                        class="border-gray-300 rounded-md text-sm shadow-sm focus:ring-gray-500 focus:border-gray-500"
                    >
                        <option value="combined" {{ $viewMode === 'combined' ? 'selected' : '' }}>Типи виробу (об'єднана таблиця) і послуги окремо</option>
                        <option value="grouped" {{ $viewMode === 'grouped' ? 'selected' : '' }}>Кожен виріб разом з його послугою</option>
                        <option value="combined_services" {{ $viewMode === 'combined_services' ? 'selected' : '' }}>Типи виробу (об'єднана таблиця) + Послуги (об'єднана таблиця)</option>
                    </select>
                @endif
            </div>
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
                        <div><span class="font-semibold">Коефіцієнт терміновості:</span> {{ $urgencyCoefficientDisplay }}</div>
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

            @if($hasMultipleProducts && in_array($viewMode, ['combined', 'combined_services'], true))
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 border-b font-semibold text-gray-800">
                        Типи виробу (об'єднана таблиця)
                    </div>
                    <table class="min-w-full table-fixed text-sm border-b border-gray-200">
                        <thead style="background-color: #D3D4D4;">
                            <tr>
                                <th class="px-3 py-2 border text-left">Виріб №</th>
                                <th class="px-3 py-2 border text-left" style="max-width: 150px; width: 150px;">Тип виробу</th>
                                <th class="px-3 py-2 border text-left">Позиція замовлення</th>
                                <th class="px-3 py-2 border text-left">Матеріал</th>
                                <th class="px-3 py-2 border text-left">Товщина</th>
                                <th class="px-3 py-2 border text-left" style="max-width: 100px; width: 100px;">Ширина(м)</th>
                                <th class="px-3 py-2 border text-left" style="max-width: 100px; width: 100px;">Висота(м)</th>
                                <th class="px-3 py-2 border text-left" style="max-width: 100px; width: 100px;">Кількісь(шт)</th>
                                <th class="px-3 py-2 border text-left">Шари друку (шт):CMYK</th>
                                <th class="px-3 py-2 border text-left">Шари друку (шт):Білий</th>
                                <th class="px-3 py-2 border text-right" style="max-width: 100px; width: 100px;">Ціна</th>
                                <th class="px-3 py-2 border text-right" style="max-width: 100px; width: 100px;">Вартість виробу</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                @php
                                    $positions = is_array($product['positions'] ?? null) ? $product['positions'] : [];
                                    $isUv = mb_strtolower((string)($product['productTypeName'] ?? ''), 'UTF-8') === mb_strtolower('УФ друк', 'UTF-8');
                                    $isSheet = (string)($product['materialType'] ?? '') === 'Листовий';
                                    $productTotalCost = array_reduce($positions, static function (float $sum, array $position): float {
                                        $cost = $position['cost'] ?? null;
                                        if ($cost === null || $cost === '' || !is_numeric($cost)) {
                                            return $sum;
                                        }

                                        return $sum + (float) $cost;
                                    }, 0.0);
                                @endphp
                                @if(count($positions) === 0)
                                    <tr>
                                        <td class="px-3 py-2 border">{{ $product['display_index'] ?? ($product['index'] ?? 'Н/Д') }}</td>
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
                                            $isZeroPriceRow = $hasPositionCost && abs(round((float)$positionCost, 2)) < 0.000001;
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
                                            $uvBothEmpty = $isUv && $cmykValue === '—' && $whiteValue === '—';
                                            $danger = 'background-color:#F01326;color:#fff;';
                                            $typeValue = ($product['productTypeName'] ?? '') !== '' ? $product['productTypeName'] : 'Н/Д';
                                            $isCleanWithCustomerMaterial = $typeValue === 'Чистий матеріал'
                                                && in_array($materialValue, ['Матеріал замовника рулонний', 'Матеріал замовника листовий'], true);
                                            $typeCellStyle = $typeValue === 'Н/Д' ? $danger : '';
                                            $materialCellStyle = $materialValue === '—' ? $danger : $cellStyle($materialValue);
                                            $cmykCellStyle = $uvBothEmpty ? $danger : $cellStyle($cmykValue);
                                            $whiteCellStyle = $uvBothEmpty ? $danger : $cellStyle($whiteValue);
                                            $widthCellStyle = ($isCleanWithCustomerMaterial && $isZeroLike($widthValue)) ? $danger : $cellStyle($widthValue);
                                            $heightCellStyle = ($isCleanWithCustomerMaterial && $isZeroLike($heightValue)) ? $danger : $cellStyle($heightValue);
                                            $qtyCellStyle = ($isCleanWithCustomerMaterial && $isZeroLike($qtyValue)) ? $danger : $cellStyle($qtyValue);
                                        @endphp
                                        <tr>
                                            @if($idx === 0)
                                                <td class="px-3 py-2 border align-top" rowspan="{{ max(count($positions), 1) }}">{{ $product['display_index'] ?? ($product['index'] ?? 'Н/Д') }}</td>
                                                <td class="px-3 py-2 border align-top" style="max-width: 150px; width: 150px; {{ $typeCellStyle }}" rowspan="{{ max(count($positions), 1) }}">{{ $typeValue }}</td>
                                            @endif
                                            <td class="px-3 py-2 border">{{ $position['index'] ?? ($idx + 1) }}</td>
                                            <td class="px-3 py-2 border" style="{{ $materialCellStyle }}">{{ $materialValue }}</td>
                                            <td class="px-3 py-2 border" style="{{ $cellStyle($thicknessValue) }}">{{ $thicknessValue }}</td>
                                            <td class="px-3 py-2 border" style="max-width: 100px; width: 100px; {{ $widthCellStyle }}">{{ $widthValue }}</td>
                                            <td class="px-3 py-2 border" style="max-width: 100px; width: 100px; {{ $heightCellStyle }}">{{ $heightValue }}</td>
                                            <td class="px-3 py-2 border" style="max-width: 100px; width: 100px; {{ $qtyCellStyle }}">{{ $qtyValue }}</td>
                                            <td class="px-3 py-2 border" style="{{ $cmykCellStyle }}">{{ $cmykValue }}</td>
                                            <td class="px-3 py-2 border" style="{{ $whiteCellStyle }}">{{ $whiteValue }}</td>
                                            <td class="px-3 py-2 border text-right" style="max-width: 100px; width: 100px; {{ $cellStyle($costValue) }}">{{ $costValue }}</td>
                                            @if($idx === 0)
                                                <td class="px-3 py-2 border text-right align-top font-semibold" style="max-width: 100px; width: 100px;" rowspan="{{ max(count($positions), 1) }}">
                                                    {{ number_format($productTotalCost, 2, '.', ' ') }}
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                    @php
                        $combinedProductsSubtotal = collect($products)->sum(function ($product) {
                            $positions = is_array($product['positions'] ?? null) ? $product['positions'] : [];

                            return array_reduce($positions, static function (float $sum, array $position): float {
                                $cost = $position['cost'] ?? null;
                                if ($cost === null || $cost === '' || !is_numeric($cost)) {
                                    return $sum;
                                }

                                return $sum + (float) $cost;
                            }, 0.0);
                        });
                    @endphp
                    <div class="px-4 py-3 border-t text-right text-sm">
                        <span class="font-semibold">Загальна вартість матеріалів:</span>
                        <span class="font-bold">{{ number_format($combinedProductsSubtotal, 2, '.', ' ') }}</span>
                    </div>
                </div>
            @endif

            @if($hasMultipleProducts && $viewMode === 'combined_services')
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 border-b font-semibold text-gray-800">
                        Послуги (об'єднана таблиця)
                    </div>
                    <table class="min-w-full table-fixed text-sm border-b border-gray-200">
                        <thead style="background-color: #D3D4D4;">
                            <tr>
                                <th class="px-3 py-2 border text-left" style="width: 200px;">Послуги до виробу</th>
                                <th class="px-3 py-2 border text-left" style="max-width: 150px; width: 150px;">Назва послуги</th>
                                <th class="px-3 py-2 border text-left">Опис</th>
                                <th class="px-3 py-2 border text-right" style="max-width: 100px; width: 100px;">Ціна</th>
                                <th class="px-3 py-2 border text-right" style="max-width: 140px; width: 140px;">Вартість послуг</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $hasCombinedServiceRows = false;
                            @endphp
                            @foreach($products as $product)
                                @php
                                    $positions = is_array($product['positions'] ?? null) ? $product['positions'] : [];
                                    $serviceRows = is_array($product['service_rows'] ?? null) ? $product['service_rows'] : [];
                                    $servicesEnabledRaw = $product['servicesEnabledRaw'] ?? ($product['services_enabled'] ?? null);
                                    $servicesEnabled = in_array((string)$servicesEnabledRaw, ['1', 'true', 'yes'], true) || $servicesEnabledRaw === true || $servicesEnabledRaw === 1;
                                    $showServices = count($positions) === 1 && $servicesEnabled;
                                @endphp

                                @if($showServices)
                                    @php
                                        $hasCombinedServiceRows = true;
                                        $groupRows = max(count($serviceRows), 1);
                                    @endphp
                                    @if(count($serviceRows) > 0)
                                        @foreach($serviceRows as $sIndex => $row)
                                            <tr>
                                                @if($sIndex === 0)
                                                    <td class="px-3 py-2 border align-top" style="width: 200px;" rowspan="{{ $groupRows }}">Послуги до виробу #{{ $product['display_index'] ?? ($product['index'] ?? 1) }}</td>
                                                @endif
                                                <td class="px-3 py-2 border" style="max-width: 150px; width: 150px;">
                                                    {{ $row['name'] ?? '—' }}
                                                    @if(($row['key'] ?? '') === 'rolling' && !empty($row['rolling_individual']))
                                                        <strong>(Індивідуально)</strong>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 border">
                                                    @if(($row['key'] ?? '') === 'rolling' && is_array($row['rolling_meta'] ?? null))
                                                        @php
                                                            $meta = $row['rolling_meta'];
                                                        @endphp
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
                                                <td class="px-3 py-2 border text-right" style="max-width: 100px; width: 100px;">{{ number_format((float)($row['cost'] ?? 0), 2, '.', ' ') }}</td>
                                                @if($sIndex === 0)
                                                    <td class="px-3 py-2 border text-right align-top font-semibold" style="max-width: 140px; width: 140px;" rowspan="{{ $groupRows }}">
                                                        {{ number_format((float)($product['services_cost'] ?? 0), 2, '.', ' ') }}
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td class="px-3 py-2 border" style="width: 200px;">Послуги до виробу #{{ $product['display_index'] ?? ($product['index'] ?? 1) }}</td>
                                            <td class="px-3 py-2 border" style="max-width: 150px; width: 150px;">—</td>
                                            <td class="px-3 py-2 border text-gray-500">Послуги не застосовані.</td>
                                            <td class="px-3 py-2 border text-right" style="max-width: 100px; width: 100px;">0.00</td>
                                            <td class="px-3 py-2 border text-right font-semibold" style="max-width: 140px; width: 140px;">{{ number_format((float)($product['services_cost'] ?? 0), 2, '.', ' ') }}</td>
                                        </tr>
                                    @endif
                                @endif
                            @endforeach
                            @if(!$hasCombinedServiceRows)
                                <tr>
                                    <td class="px-3 py-2 border text-gray-500" colspan="5">Послуги не застосовані.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    @php
                        $combinedServicesSubtotal = collect($products)->sum(function ($product) {
                            $positions = is_array($product['positions'] ?? null) ? $product['positions'] : [];
                            $servicesEnabledRaw = $product['servicesEnabledRaw'] ?? ($product['services_enabled'] ?? null);
                            $servicesEnabled = in_array((string)$servicesEnabledRaw, ['1', 'true', 'yes'], true) || $servicesEnabledRaw === true || $servicesEnabledRaw === 1;
                            $showServices = count($positions) === 1 && $servicesEnabled;

                            return $showServices ? (float)($product['services_cost'] ?? 0) : 0;
                        });
                    @endphp
                    <div class="px-4 py-3 border-t text-right text-sm">
                        <span class="font-semibold">Загальна вартість послуг:</span>
                        <span class="font-bold">{{ number_format($combinedServicesSubtotal, 2, '.', ' ') }}</span>
                    </div>
                </div>
            @endif

            @forelse($products as $product)
                @php
                    $positions = is_array($product['positions'] ?? null) ? $product['positions'] : [];
                    $serviceRows = is_array($product['service_rows'] ?? null) ? $product['service_rows'] : [];
                    $servicesEnabledRaw = $product['servicesEnabledRaw'] ?? ($product['services_enabled'] ?? null);
                    $servicesEnabled = in_array((string)$servicesEnabledRaw, ['1', 'true', 'yes'], true) || $servicesEnabledRaw === true || $servicesEnabledRaw === 1;
                    $showServices = count($positions) === 1 && $servicesEnabled;
                    $isUv = mb_strtolower((string)($product['productTypeName'] ?? ''), 'UTF-8') === mb_strtolower('УФ друк', 'UTF-8');
                    $showThickness = (string)($product['materialType'] ?? '') === 'Листовий';
                @endphp

                @if((!$hasMultipleProducts || $viewMode === 'grouped') || ($hasMultipleProducts && $viewMode === 'combined' && $showServices))
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    @if(!$hasMultipleProducts || $viewMode === 'grouped')
                        <div class="px-4 py-3 bg-gray-50 border-b font-semibold text-gray-800">
                            Тип виробу #{{ $product['display_index'] ?? ($product['index'] ?? 1) }}: {{ $product['productTypeName'] ?? '—' }}
                        </div>

                        <table class="min-w-full table-fixed text-sm border-b border-gray-200">
                            <thead style="background-color: #D3D4D4;">
                                <tr>
                                    <th class="px-3 py-2 border text-left" style="width: 200px;">Тип виробу</th>
                                    @if(count($positions) > 1)
                                        <th class="px-3 py-2 border text-left">Позиція замовлення</th>
                                    @endif
                                    <th class="px-3 py-2 border text-left">Матеріал</th>
                                    @if($showThickness)
                                        <th class="px-3 py-2 border text-left">Товщина</th>
                                    @endif
                                    <th class="px-3 py-2 border text-left" style="max-width: 100px; width: 100px;">Ширина(м)</th>
                                    <th class="px-3 py-2 border text-left" style="max-width: 100px; width: 100px;">Висота(м)</th>
                                    <th class="px-3 py-2 border text-left" style="max-width: 100px; width: 100px;">Кількісь(шт)</th>
                                    @if($isUv)
                                        <th class="px-3 py-2 border text-left">Шари друку (шт):CMYK</th>
                                        <th class="px-3 py-2 border text-left">Шари друку (шт):Білий</th>
                                    @endif
                                    <th class="px-3 py-2 border text-right" style="max-width: 100px; width: 100px;">Ціна</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($positions as $idx => $position)
                                    @php
                                        $hasPositionCost = array_key_exists('cost', $position) && $position['cost'] !== null && $position['cost'] !== '';
                                        $positionCost = $hasPositionCost ? (float)$position['cost'] : null;
                                        $isZeroPriceRow = $hasPositionCost && abs(round((float)$positionCost, 2)) < 0.000001;
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
                                        $thicknessValue = ($product['manualThickness'] ?? '') !== '' ? $product['manualThickness'] : ($product['thickness'] ?? '—');
                                        $widthValue = ($position['width'] ?? '') !== '' ? $position['width'] : '—';
                                        $heightValue = ($position['height'] ?? '') !== '' ? $position['height'] : '—';
                                        $qtyValue = ($position['qty'] ?? '') !== '' ? $position['qty'] : '—';
                                        $cmykValue = ((int)($position['cmyk'] ?? 0) > 0) ? ($position['cmyk'] ?? 0) : '—';
                                        $whiteValue = ((int)($position['white'] ?? 0) > 0) ? ($position['white'] ?? 0) : '—';
                                        $costValue = $hasPositionCost ? number_format((float)$position['cost'], 2, '.', ' ') : 'Н/Д';
                                        $uvBothEmpty = $isUv && $cmykValue === '—' && $whiteValue === '—';
                                        $danger = 'background-color:#F01326;color:#fff;';
                                        $typeValue = $product['productTypeName'] ?? '—';
                                        $isCleanWithCustomerMaterial = $typeValue === 'Чистий матеріал'
                                            && in_array($materialValue, ['Матеріал замовника рулонний', 'Матеріал замовника листовий'], true);
                                        $typeCellStyle = $typeValue === 'Н/Д' ? $danger : '';
                                        $materialCellStyle = $materialValue === '—' ? $danger : $cellStyle($materialValue);
                                        $cmykCellStyle = $uvBothEmpty ? $danger : $cellStyle($cmykValue);
                                        $whiteCellStyle = $uvBothEmpty ? $danger : $cellStyle($whiteValue);
                                        $widthCellStyle = ($isCleanWithCustomerMaterial && $isZeroLike($widthValue)) ? $danger : $cellStyle($widthValue);
                                        $heightCellStyle = ($isCleanWithCustomerMaterial && $isZeroLike($heightValue)) ? $danger : $cellStyle($heightValue);
                                        $qtyCellStyle = ($isCleanWithCustomerMaterial && $isZeroLike($qtyValue)) ? $danger : $cellStyle($qtyValue);
                                    @endphp
                                    <tr>
                                        @if($idx === 0)
                                            <td class="px-3 py-2 border align-top" style="width: 200px; {{ $typeCellStyle }}" rowspan="{{ max(count($positions), 1) }}">{{ $typeValue }}</td>
                                        @endif
                                        @if(count($positions) > 1)
                                            <td class="px-3 py-2 border">{{ $position['index'] ?? ($idx + 1) }}</td>
                                        @endif
                                        <td class="px-3 py-2 border" style="{{ $materialCellStyle }}">{{ $materialValue }}</td>
                                        @if($showThickness)
                                            <td class="px-3 py-2 border" style="{{ $cellStyle($thicknessValue) }}">{{ $thicknessValue }}</td>
                                        @endif
                                        <td class="px-3 py-2 border" style="max-width: 100px; width: 100px; {{ $widthCellStyle }}">{{ $widthValue }}</td>
                                        <td class="px-3 py-2 border" style="max-width: 100px; width: 100px; {{ $heightCellStyle }}">{{ $heightValue }}</td>
                                        <td class="px-3 py-2 border" style="max-width: 100px; width: 100px; {{ $qtyCellStyle }}">{{ $qtyValue }}</td>
                                        @if($isUv)
                                            <td class="px-3 py-2 border" style="{{ $cmykCellStyle }}">{{ $cmykValue }}</td>
                                            <td class="px-3 py-2 border" style="{{ $whiteCellStyle }}">{{ $whiteValue }}</td>
                                        @endif
                                        <td class="px-3 py-2 border text-right" style="max-width: 100px; width: 100px; {{ $cellStyle($costValue) }}">{{ $costValue }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-3 py-2 border text-gray-500" colspan="{{ $isUv ? 10 : 8 }}">Позиції відсутні.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif

                    @if($showServices)
                        <table class="min-w-full table-fixed text-sm">
                            <thead style="background-color: #D3D4D4;">
                                <tr>
                                    <th class="px-3 py-2 border text-left" style="width: 200px;">Послуги до виробу</th>
                                    <th class="px-3 py-2 border text-left" style="max-width: 150px; width: 150px;">Назва послуги</th>
                                    <th class="px-3 py-2 border text-left">Опис</th>
                                    <th class="px-3 py-2 border text-right" style="max-width: 100px; width: 100px;">Ціна</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($serviceRows as $sIndex => $row)
                                    <tr>
                                        @if($sIndex === 0)
                                            <td class="px-3 py-2 border align-top" style="width: 200px;" rowspan="{{ max(count($serviceRows), 1) }}">Послуги до виробу #{{ $product['display_index'] ?? ($product['index'] ?? 1) }}</td>
                                        @endif
                                        <td class="px-3 py-2 border" style="max-width: 150px; width: 150px;">
                                            {{ $row['name'] ?? '—' }}
                                            @if(($row['key'] ?? '') === 'rolling' && !empty($row['rolling_individual']))
                                                <strong>(Індивідуально)</strong>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 border">
                                            @if(($row['key'] ?? '') === 'rolling' && is_array($row['rolling_meta'] ?? null))
                                                @php
                                                    $meta = $row['rolling_meta'];
                                                @endphp
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
                                        <td class="px-3 py-2 border text-right" style="max-width: 100px; width: 100px;">{{ number_format((float)($row['cost'] ?? 0), 2, '.', ' ') }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-3 py-2 border text-gray-500" colspan="4">Послуги не застосовані.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif

                    @php
                        $footerLabel = $showServices
                            ? 'Вартість послуг до виробу #'.($product['display_index'] ?? ($product['index'] ?? 1)).':'
                            : 'Вартість виробу:';
                        $footerValue = $showServices
                            ? (float)($product['services_cost'] ?? 0)
                            : (float)($product['total_cost'] ?? 0);
                    @endphp
                    <div class="px-4 py-3 border-t text-right text-sm">
                        <span class="font-semibold">{{ $footerLabel }}</span>
                        <span class="font-bold">{{ number_format($footerValue, 2, '.', ' ') }}</span>
                    </div>
                </div>
                @endif
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-500">У заявці немає збережених блоків виробів.</div>
            @endforelse

            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="text-sm font-semibold text-gray-700">
                        @if ($minimumOrderApplied)
                            Врахована вартість мінімального замовлення —100грн.
                        @endif
                    </div>
                    <span class="font-bold text-lg">Всього: {{ number_format($summaryOrderTotal, 2, '.', ' ') }}</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
