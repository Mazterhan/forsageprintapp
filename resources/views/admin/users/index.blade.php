<x-app-layout>
    @section('title', __('Користувачі та їх доступи'))
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Користувачі та їх доступи') }}
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    Створити роль з доступами
                </a>
                <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    Створити користувача
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->has('toggle'))
                <div class="mb-4 text-sm text-red-700 bg-red-100 px-4 py-2 rounded">
                    {{ $errors->first('toggle') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Користувачі') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($users as $user)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $user->id }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $user->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $user->email }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $user->role }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            @if ($user->is_active)
                                                <span class="inline-flex items-center px-2 py-1 text-xs bg-green-100 text-green-700 rounded">Active</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-1 text-xs bg-gray-200 text-gray-700 rounded">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-indigo-600 hover:text-indigo-900">
                                                    {{ $user->is_active ? __('Deactivate') : __('Activate') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>

            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">{{ __('Ролі доступу') }}</h3>
                        <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                            Створити роль з доступами
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Назва ролі</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Код</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Аналітика</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Замовлення</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Прайс</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Адміністрування</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Дії</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($roles as $role)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $role->id }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            <a href="{{ route('admin.roles.edit', $role) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $role->name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $role->slug }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $role->can_analytics ? 'доступно' : 'недоступно' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $role->can_orders ? 'доступно' : 'недоступно' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $role->can_price ? 'доступно' : 'недоступно' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $role->can_admin ? 'доступно' : 'недоступно' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            <a href="{{ route('admin.roles.edit', $role) }}" class="text-indigo-600 hover:text-indigo-900">
                                                Редагувати
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('Ролі ще не створено.') }}
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
