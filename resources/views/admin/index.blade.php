<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Сторінка Адміністратора') }}
            </h2>
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('Створити користувача') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-col gap-3">
                        <div>{{ __('Зона адміністратора та менеджера.') }}</div>
                        <a href="{{ route('admin.users.index') }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ __('Керування користувачами') }}
                        </a>
                        <a href="{{ route('admin.departments.index') }}" class="text-indigo-600 hover:text-indigo-900">
                            {{ __('Керування підрозділами') }}
                        </a>
                    </div>
                </div>
            </div>

            @php
                $currentSort = request('sort');
                $currentDirection = request('direction', 'asc');
            @endphp
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Користувачі та підрозділи') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $nextDirection = $currentSort === 'user' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('admin.index', array_merge(request()->query(), ['sort' => 'user', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                            Користувач
                                            @if ($currentSort === 'user')
                                                <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $nextDirection = $currentSort === 'department' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('admin.index', array_merge(request()->query(), ['sort' => 'department', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                            Підрозділ
                                            @if ($currentSort === 'department')
                                                <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $nextDirection = $currentSort === 'direction' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('admin.index', array_merge(request()->query(), ['sort' => 'direction', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                            Напрям
                                            @if ($currentSort === 'direction')
                                                <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $nextDirection = $currentSort === 'position' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('admin.index', array_merge(request()->query(), ['sort' => 'position', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                            Посада
                                            @if ($currentSort === 'position')
                                                <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        @php
                                            $nextDirection = $currentSort === 'role' && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        @endphp
                                        <a href="{{ route('admin.index', array_merge(request()->query(), ['sort' => 'role', 'direction' => $nextDirection])) }}" class="inline-flex items-center gap-1">
                                            Роль доступу
                                            @if ($currentSort === 'role')
                                                <span class="text-gray-600">{{ $currentDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($activeUsers as $user)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $user->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $user->department?->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $user->departmentCategory?->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $user->position?->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $user->role }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('Немає активних користувачів.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
