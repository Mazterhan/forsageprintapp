<x-app-layout>
    @section('title', __('Замовлення'))
    @php
        $ordersPermissions = $ordersPermissions ?? [];
        $formatOrderMoney = static function ($value): string {
            $formatted = number_format((float) $value, 2, '.', ' ');
            return preg_replace('/\.0+$/', '', $formatted) ?? $formatted;
        };
        $formatOrderDate = static fn ($date): string => $date
            ? $date->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i')
            : '';
        $nextDir = fn (string $column): string => ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
        $sortLink = fn (string $column): string => route('orders.index', array_merge(
            request()->query(),
            ['sort' => $column, 'direction' => $nextDir($column)]
        ));
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Замовлення') }}
                </h2>
                @if($ordersPermissions['calculation'] ?? false)
                    <a href="{{ route('orders.calculation') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        {{ __('Прорахунок замовлення') }}
                    </a>
                @endif
                @if($ordersPermissions['proposals'] ?? false)
                    <a href="{{ route('orders.proposals') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        {{ __('Збережені заявки') }}
                    </a>
                @endif
                <a href="{{ route('orders.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    {{ __('Створити замовлення') }}
                </a>
            </div>
            <div class="flex items-center gap-2">
                @if($ordersPermissions['clients'] ?? false)
                    <a href="{{ route('orders.clients.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        {{ __('Замовники') }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <style>
        .orders-table thead tr {
            background-color: #FCEEDF;
        }

        .order-row {
            transition: background-color 0.5s ease, background-image 0.5s ease;
        }

        .order-row td {
            background: transparent;
        }

        .order-row.row-alt {
            background-color: #F9FAFB;
        }

        .order-row.row-base {
            background-color: #FFFFFF;
        }

        .order-row:hover {
            background-color: #D8F1F2;
            background-image: linear-gradient(90deg, #e9f7f7 0%, #D8F1F2 100%);
        }
    </style>

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12">
            <div class="mb-4 flex items-center gap-2">
                <label for="orders-per-page" class="text-sm text-gray-700 whitespace-nowrap">
                    Кількість замовлень на сторінці
                </label>
                <select
                    id="orders-per-page"
                    class="border-gray-300 rounded-md text-sm shadow-sm"
                    onchange="window.changeOrdersPerPage(this.value)"
                >
                    <option value="20" @selected($perPageRaw === '20')>20</option>
                    <option value="50" @selected($perPageRaw === '50')>50</option>
                    <option value="100" @selected($perPageRaw === '100')>100</option>
                    <option value="all" @selected($perPageRaw === 'all')>Всі</option>
                </select>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="orders-table min-w-full text-sm border border-gray-200">
                            <thead>
                                <tr>
                                    @foreach([
                                        'date' => 'Дата',
                                        'number' => 'Номер замовлення',
                                        'customer' => "Ім'я замовника",
                                        'user' => 'Користувач',
                                        'amount_due' => 'До сплати',
                                        'total_cost' => 'Вартість',
                                    ] as $column => $label)
                                        <th class="px-4 py-3 border-b {{ in_array($column, ['amount_due', 'total_cost'], true) ? 'text-right' : 'text-left' }} text-[14px] {{ $column === 'total_cost' ? 'font-bold' : '' }}">
                                            <a class="inline-flex items-center gap-1" href="{{ $sortLink($column) }}">
                                                {{ $label }}
                                                @if ($sort === $column)
                                                    <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                                @else
                                                    <span class="text-gray-400">↕</span>
                                                @endif
                                            </a>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    <tr class="order-row {{ $loop->odd ? 'row-alt' : 'row-base' }}" tabindex="0">
                                        <td class="px-4 py-3 border-b">{{ $formatOrderDate($order->updated_at) }}</td>
                                        <td class="px-4 py-3 border-b">
                                            <a href="{{ route('orders.show', $order) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $order->order_number }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 border-b">{{ $order->customer_name ?: '—' }}</td>
                                        <td class="px-4 py-3 border-b">{{ $order->lastEditedBy?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 border-b text-right">{{ $formatOrderMoney($order->amount_due) }}</td>
                                        <td class="px-4 py-3 border-b text-right font-bold">{{ $formatOrderMoney($order->total_cost) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                            Замовлення ще не створено.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $orders->links() }}</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.changeOrdersPerPage = function (value) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        };
    </script>
</x-app-layout>
