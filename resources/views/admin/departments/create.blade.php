<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Створити підрозділ') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('warnings'))
                <div class="text-sm text-amber-700 bg-amber-100 px-4 py-2 rounded">
                    <ul class="list-disc list-inside">
                        @foreach (session('warnings') as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.departments.store') }}" class="space-y-6">
                        @csrf
                        @include('admin.departments.partials.form', [
                            'department' => $department,
                            'categories' => $categories,
                            'positions' => $positions,
                            'lockedPositions' => $lockedPositions,
                            'activeUsers' => $activeUsers,
                        ])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
