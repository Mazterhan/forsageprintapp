<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Керування підрозділами') }}
            </h2>
            <a href="{{ route('admin.departments.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('Створити підрозділ') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Назва підрозділу</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Категорії</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Посади</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Дія</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($departments as $department)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $department->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $department->categories_count }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $department->positions_count }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            <a href="{{ route('admin.departments.edit', $department) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ __('Коригувати') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('Немає підрозділів.') }}
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
