<x-app-layout>
    @section('title', $title)
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $title }}</h2>
            <a href="{{ route('price.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('додати позицію') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12">
            @if (session('status'))
                <div class="mb-4 text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="w-full overflow-x-auto">
                        <table class="min-w-full w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php($next = $sort === 'internal_code' && $direction === 'asc' ? 'desc' : 'asc')
                                        <a href="{{ route('price.index', array_merge(request()->query(), ['sort' => 'internal_code', 'direction' => $next])) }}" class="inline-flex items-center gap-1">
                                            Внутрішній код
                                            @if ($sort === 'internal_code')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php($next = $sort === 'name' && $direction === 'asc' ? 'desc' : 'asc')
                                        <a href="{{ route('price.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => $next])) }}" class="inline-flex items-center gap-1">
                                            Назва товару
                                            @if ($sort === 'name')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php($next = $sort === 'category' && $direction === 'asc' ? 'desc' : 'asc')
                                        <a href="{{ route('price.index', array_merge(request()->query(), ['sort' => 'category', 'direction' => $next])) }}" class="inline-flex items-center gap-1">
                                            Категорія товару
                                            @if ($sort === 'category')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php($next = $sort === 'purchase_price' && $direction === 'asc' ? 'desc' : 'asc')
                                        <a href="{{ route('price.index', array_merge(request()->query(), ['sort' => 'purchase_price', 'direction' => $next])) }}" class="inline-flex items-center gap-1">
                                            Закупівельна ціна
                                            @if ($sort === 'purchase_price')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Націнка %</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                        @php($next = $sort === 'service_price' && $direction === 'asc' ? 'desc' : 'asc')
                                        <a href="{{ route('price.index', array_merge(request()->query(), ['sort' => 'service_price', 'direction' => $next])) }}" class="inline-flex items-center gap-1 justify-center">
                                            Роздрібна ціна
                                            @if ($sort === 'service_price')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Дія</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($items as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $item->internal_code }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            <a href="{{ route('price.show', $item) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $item->name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $item->category }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            {{ $item->purchase_price !== null ? number_format((float) $item->purchase_price, 2, '.', '') : '' }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700 text-center">
                                            @if ($item->purchase_price !== null && (float) $item->purchase_price > 0 && $item->service_price !== null)
                                                {{ number_format((((float) $item->service_price - (float) $item->purchase_price) / (float) $item->purchase_price) * 100, 2, '.', '') }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700 text-center">
                                            {{ $item->service_price !== null ? number_format((float) $item->service_price, 2, '.', '') : '' }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700 text-center">
                                            <form method="POST" action="{{ route('price.toggle', $item) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-gray-600 hover:text-gray-900">
                                                    {{ $item->is_active ? __('Деактивувати') : __('Активувати') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('Записів не знайдено.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $items->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
