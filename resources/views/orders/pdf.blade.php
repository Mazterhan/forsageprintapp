<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Замовлення {{ $order->order_number }}</title>
    <style>
        @page { margin: 28px 34px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #1f2937;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }
        h1 { margin: 0; font-size: 20px; }
        .header { margin-bottom: 22px; border-bottom: 2px solid #000000; padding-bottom: 14px; }
        .header-table, .totals-table { width: 100%; border-collapse: collapse; }
        .header-table td { padding: 3px 0; vertical-align: top; }
        .label { width: 145px; color: #6b7280; }
        .value { font-weight: 700; }
        .order-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .order-table thead { display: table-header-group; }
        .order-table th {
            border: 1px solid #9ca3af;
            background: #e5e7eb;
            padding: 8px 6px;
            text-align: center;
            font-weight: 700;
        }
        .order-table td { border: 1px solid #d1d5db; padding: 7px 6px; vertical-align: top; }
        .number { width: 7%; text-align: center; }
        .nomenclature { width: 45%; overflow-wrap: break-word; }
        .quantity { width: 13%; text-align: center; }
        .money { width: 17.5%; text-align: right; white-space: nowrap; }
        .totals { margin-top: 18px; margin-left: auto; width: 310px; }
        .totals-table td { padding: 5px 0; }
        .totals-table .total-label { padding-right: 18px; font-weight: 700; }
        .totals-table .total-value { text-align: right; white-space: nowrap; font-weight: 700; }
        .amount-due td { border-top: 1px solid #9ca3af; padding-top: 8px; font-size: 12px; }
    </style>
</head>
<body>
    @php
        $formatMoney = static function ($value): string {
            $formatted = number_format((float) $value, 2, '.', ' ');
            return preg_replace('/\.0+$/', '', $formatted) ?? $formatted;
        };
    @endphp

    <div class="header">
        <h1>Замовлення {{ $order->order_number }}</h1>
    </div>

    <table class="header-table">
        <tr>
            <td class="label">Виконавець замовлення:</td>
            <td class="value">ТОВ Форсаж-Прінт</td>
        </tr>
        <tr>
            <td class="label">Замовник:</td>
            <td class="value">{{ $order->client?->name ?? $order->customer_name }}</td>
        </tr>
        <tr>
            <td class="label">Дата замовлення:</td>
            <td class="value">{{ $order->created_at?->copy()->timezone('Europe/Kiev')->format('d.m.Y') ?? '—' }}</td>
        </tr>
    </table>

    <table class="order-table" style="margin-top: 20px;">
        <thead>
            <tr>
                <th class="number">№</th>
                <th class="nomenclature">Номенклатура</th>
                <th class="quantity">Кількість</th>
                <th class="money">Вартість за одн.</th>
                <th class="money">Сума</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
                @php
                    $quantity = (int) ($item['quantity'] ?? 0);
                    $unitCost = (int) ($item['unit_cost'] ?? 0);
                    $itemSum = isset($item['sum']) ? (int) $item['sum'] : $quantity * $unitCost;
                @endphp
                <tr>
                    <td class="number">{{ $index + 1 }}</td>
                    <td class="nomenclature">{{ $item['nomenclature'] ?? '—' }}</td>
                    <td class="quantity">{{ $quantity }}</td>
                    <td class="money">{{ $formatMoney($unitCost) }} грн</td>
                    <td class="money">{{ $formatMoney($itemSum) }} грн</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table class="totals-table">
            <tr>
                <td class="total-label">Сума з ПДВ</td>
                <td class="total-value">{{ $formatMoney($order->total_cost) }} грн</td>
            </tr>
            <tr>
                <td class="total-label">Загальна сума сплат</td>
                <td class="total-value">{{ $formatMoney($paymentsTotal) }} грн</td>
            </tr>
            <tr class="amount-due">
                <td class="total-label">Сума до сплати</td>
                <td class="total-value">{{ $formatMoney($amountDue) }} грн</td>
            </tr>
        </table>
    </div>
</body>
</html>
