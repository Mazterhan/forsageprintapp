<x-app-layout>
    @section('title', __('Збережені заявки'))
    <x-slot name="header">
        <div x-data="{}" class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Збережені заявки') }}</h2>
                <button
                    x-show="!$store.proposalManage.mode"
                    x-cloak
                    type="button"
                    @click="$store.proposalManage.mode = true"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50"
                >
                    Керування списком заявок
                </button>
            </div>
            <a href="{{ route('orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('Повернутись до замовлень') }}
            </a>
        </div>
    </x-slot>

    @php
        $nextDir = fn (string $column) => ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
        $sortLink = fn (string $column) => route('orders.proposals', array_merge(request()->query(), ['sort' => $column, 'direction' => $nextDir($column)]));
    @endphp

    <style>
        [x-cloak] { display: none !important; }

        .proposal-table thead tr {
            background-color: #FCEEDF;
        }

        .proposal-row {
            transition: background-color 0.5s ease, background-image 0.5s ease;
        }

        .proposal-row td {
            background: transparent;
            transition: border-color 0.5s ease;
        }

        .proposal-row.row-alt {
            background-color: #F9FAFB;
        }

        .proposal-row.row-base {
            background-color: #FFFFFF;
        }

        .proposal-row:hover {
            background-image: linear-gradient(90deg, #e9f7f7 0%, #D8F1F2 100%);
            background-color: #D8F1F2;
        }

        .proposal-row.is-active td {
            border-top: 2px solid #C3C3C3 !important;
            border-bottom: 2px solid #C3C3C3 !important;
        }

        .proposal-row.is-active td:first-child {
            border-left: 2px solid #C3C3C3 !important;
        }

        .proposal-row.is-active td:last-child {
            border-right: 2px solid #C3C3C3 !important;
        }
    </style>

    <div class="py-12">
        <div x-data="{}" class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full">
                <div class="p-6 text-gray-900">
                    <div x-show="$store.proposalManage.mode" x-cloak class="mb-4 flex items-center justify-between gap-4">
                        <button
                            type="button"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50"
                        >
                            Об'єднати заявки
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-semibold text-white"
                            style="background-color: #DC2626;"
                        >
                            Видалити заявки
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="proposal-table min-w-full text-sm border border-gray-200">
                            <thead>
                                <tr>
                                    <th x-show="$store.proposalManage.mode" x-cloak class="px-4 py-3 border-b text-left text-[14px]">#</th>
                                    <th class="px-4 py-3 border-b text-left text-[14px]">
                                        <a class="inline-flex items-center gap-1" href="{{ $sortLink('date') }}">
                                            Дата
                                            @if ($sort === 'date')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 border-b text-left text-[14px]">
                                        <a class="inline-flex items-center gap-1" href="{{ $sortLink('number') }}">
                                            Номер заявки
                                            @if ($sort === 'number')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 border-b text-left text-[14px]">
                                        <a class="inline-flex items-center gap-1" href="{{ $sortLink('client') }}">
                                            Ім'я замовника
                                            @if ($sort === 'client')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 border-b text-left text-[14px]">
                                        <a class="inline-flex items-center gap-1" href="{{ $sortLink('user') }}">
                                            Користувач
                                            @if ($sort === 'user')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 border-b text-right text-[14px]">Вартість</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($proposals as $proposal)
                                    <tr class="proposal-row {{ $loop->odd ? 'row-alt' : 'row-base' }}" tabindex="0">
                                        <td x-show="$store.proposalManage.mode" x-cloak class="px-4 py-3 border-b">
                                            <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="px-4 py-3 border-b">
                                            {{ optional(((int) ($proposal->corrections_count ?? 0)) > 0 ? $proposal->updated_at : $proposal->created_at)->format('d.m.Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3 border-b">
                                            <a href="{{ route('orders.proposals.show', $proposal) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $proposal->proposal_number }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 border-b">{{ $proposal->client_name ?: '—' }}</td>
                                        <td class="px-4 py-3 border-b">{{ $proposal->user?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 border-b text-right">{{ number_format((float)$proposal->total_cost, 2, '.', ' ') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td :colspan="$store.proposalManage.mode ? 6 : 5" class="px-4 py-8 text-center text-gray-500">Заявки ще не збережено.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $proposals->links() }}</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            if (!Alpine.store('proposalManage')) {
                Alpine.store('proposalManage', { mode: false });
            }
        });

        (function () {
            const rows = Array.from(document.querySelectorAll('.proposal-row'));
            rows.forEach((row) => {
                row.addEventListener('click', () => {
                    rows.forEach((item) => item.classList.remove('is-active'));
                    row.classList.add('is-active');
                });
            });
        })();
    </script>
</x-app-layout>
