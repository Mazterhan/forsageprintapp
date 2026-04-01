<x-app-layout>
    @section('title', __('Збережені заявки'))
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Збережені заявки') }}</h2>
            <a href="{{ route('orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('Повернутись до замовлень') }}
            </a>
        </div>
    </x-slot>

    @php
        $nextDir = fn (string $column) => ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
        $sortLink = fn (string $column) => route('orders.proposals', array_merge(request()->query(), ['sort' => $column, 'direction' => $nextDir($column)]));
    @endphp

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm border border-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 border-b text-left"><a class="hover:underline" href="{{ $sortLink('date') }}">Дата</a></th>
                                    <th class="px-4 py-3 border-b text-left"><a class="hover:underline" href="{{ $sortLink('number') }}">Номер заявки</a></th>
                                    <th class="px-4 py-3 border-b text-left"><a class="hover:underline" href="{{ $sortLink('user') }}">Користувач</a></th>
                                    <th class="px-4 py-3 border-b text-left"><a class="hover:underline" href="{{ $sortLink('client') }}">Ім'я замовника</a></th>
                                    <th class="px-4 py-3 border-b text-right"><a class="hover:underline" href="{{ $sortLink('cost') }}">Вартість</a></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($proposals as $proposal)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 border-b">{{ optional($proposal->created_at)->format('d.m.Y H:i') }}</td>
                                        <td class="px-4 py-3 border-b">
                                            <a href="{{ route('orders.proposals.show', $proposal) }}" class="text-blue-700 hover:underline font-semibold">
                                                {{ $proposal->proposal_number }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 border-b">{{ $proposal->user?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 border-b">{{ $proposal->client_name ?: '—' }}</td>
                                        <td class="px-4 py-3 border-b text-right">{{ number_format((float)$proposal->total_cost, 2, '.', ' ') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">Заявки ще не збережено.</td>
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
</x-app-layout>
