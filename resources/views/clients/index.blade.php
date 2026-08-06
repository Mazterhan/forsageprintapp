<x-app-layout>
    @section('title', __('Замовники'))
    @php
        $nextDir = fn (string $column): string => ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
        $sortLink = fn (string $column): string => route('orders.clients.index', array_merge(
            request()->query(),
            ['sort' => $column, 'direction' => $nextDir($column)]
        ));
    @endphp
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Замовники') }}
            </h2>
            <a href="{{ route('orders.clients.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('Додати замовника') }}
            </a>
        </div>
    </x-slot>

    <style>
        .clients-table thead tr {
            background-color: #FCEEDF;
        }

        .client-row {
            transition: background-color 0.5s ease, background-image 0.5s ease;
        }

        .client-row td {
            background: transparent;
        }

        .client-row.row-alt {
            background-color: #F9FAFB;
        }

        .client-row.row-base {
            background-color: #FFFFFF;
        }

        .client-row:hover {
            background-color: #D8F1F2;
            background-image: linear-gradient(90deg, #e9f7f7 0%, #D8F1F2 100%);
        }
    </style>

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12">
            @if (session('status'))
                <div class="mb-4 text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full">
                <div class="p-6 text-gray-900 space-y-6">
                    <form method="GET" action="{{ route('orders.clients.index') }}" class="flex flex-wrap items-end gap-4">
                        <div class="flex-1 min-w-[220px]">
                            <label class="block font-medium text-sm text-gray-700" for="search">Пошук за назвою</label>
                            <input class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" id="search" name="search" type="text" value="{{ $filters['search'] ?? '' }}">
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label class="block font-medium text-sm text-gray-700" for="category">Категорія</label>
                            <select class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" id="category" name="category">
                                <option value="">Всі</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 min-w-[160px]">
                            <label class="block font-medium text-sm text-gray-700" for="vip">VIP</label>
                            <select class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" id="vip" name="vip">
                                <option value="">Всі</option>
                                <option value="1" @selected(($filters['vip'] ?? '') === '1')>Так</option>
                                <option value="0" @selected(($filters['vip'] ?? '') === '0')>Ні</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block font-medium text-sm text-gray-700" for="manager_id">Відповідальний менеджер</label>
                            <select class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" id="manager_id" name="manager_id">
                                <option value="">Всі</option>
                                @foreach ($managers as $manager)
                                    <option value="{{ $manager->id }}" @selected(($filters['manager_id'] ?? '') == $manager->id)>
                                        {{ $manager->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pt-6">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Застосувати
                            </button>
                        </div>
                    </form>

                    <div class="overflow-x-auto w-full">
                        <table class="clients-table min-w-full text-sm border border-gray-200">
                            <thead>
                                <tr>
                                    @foreach([
                                        'name' => "Ім'я / Назва",
                                        'orders_count' => 'Кількість замовлень',
                                        'payment_summary' => 'Статус оплати замовлення',
                                        'category' => 'Категорія',
                                        'vip' => 'VIP',
                                        'manager' => 'Відповідальний менеджер',
                                        'status' => 'Статус',
                                    ] as $column => $label)
                                        <th class="px-4 py-3 border-b text-left text-[14px]">
                                            @if($column === 'payment_summary')
                                                {{ $label }}
                                            @else
                                                <a class="inline-flex items-center gap-1" href="{{ $sortLink($column) }}">
                                                    {{ $label }}
                                                    @if ($sort === $column)
                                                        <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                                    @else
                                                        <span class="text-gray-400">↕</span>
                                                    @endif
                                                </a>
                                            @endif
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($clients as $client)
                                    <tr class="client-row {{ $loop->odd ? 'row-alt' : 'row-base' }}" tabindex="0">
                                        <td class="px-4 py-3 border-b">
                                            <a href="{{ route('orders.clients.show', $client) }}" class="font-medium text-indigo-600 hover:text-indigo-900">
                                                {{ $client->name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 border-b text-center font-semibold text-gray-800">
                                            {{ (int) $client->orders_count }}
                                        </td>
                                        <td class="px-4 py-3 border-b">
                                            <div class="flex min-w-[380px] flex-wrap gap-2">
                                                @if((int) $client->unpaid_orders_count > 0)
                                                    <span class="inline-flex whitespace-nowrap rounded-md border border-red-300 bg-rose-100 px-2 py-1 text-xs font-semibold text-red-800">
                                                        Відсутня: {{ (int) $client->unpaid_orders_count }}
                                                    </span>
                                                @endif
                                                @if((int) $client->partially_paid_orders_count > 0)
                                                    <span class="inline-flex whitespace-nowrap rounded-md border border-orange-500 bg-yellow-100 px-2 py-1 text-xs font-semibold text-orange-800">
                                                        Часткова: {{ (int) $client->partially_paid_orders_count }}
                                                    </span>
                                                @endif
                                                @if((int) $client->fully_paid_orders_count > 0)
                                                    <span class="inline-flex whitespace-nowrap rounded-md border border-green-300 bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">
                                                        Повна: {{ (int) $client->fully_paid_orders_count }}
                                                    </span>
                                                @endif
                                                @if((int) $client->overpaid_orders_count > 0)
                                                    <span class="inline-flex whitespace-nowrap rounded-md border border-blue-400 bg-teal-100 px-2 py-1 text-xs font-semibold text-blue-800">
                                                        Переплата: {{ (int) $client->overpaid_orders_count }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 border-b text-gray-700">{{ $client->category ?? '-' }}</td>
                                        <td class="px-4 py-3 border-b text-gray-700">{{ $client->is_vip ? 'Так' : 'Ні' }}</td>
                                        <td class="px-4 py-3 border-b text-gray-700">{{ $client->manager?->name ?? '-' }}</td>
                                        <td class="px-4 py-3 border-b text-gray-700">{{ $client->status }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                                            Замовників не знайдено.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $clients->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
