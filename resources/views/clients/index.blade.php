<x-app-layout>
    @section('title', __('Clients'))
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Clients') }}
            </h2>
            <a href="{{ route('orders.clients.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('Add client') }}
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full">
                <div class="p-6 text-gray-900 space-y-6">
                    <form method="GET" action="{{ route('orders.clients.index') }}" class="flex flex-wrap items-end gap-4">
                        <div class="flex-1 min-w-[220px]">
                            <label class="block font-medium text-sm text-gray-700" for="search">Search by name</label>
                            <input class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" id="search" name="search" type="text" value="{{ $filters['search'] ?? '' }}">
                        </div>
                        <div class="flex-1 min-w-[180px]">
                            <label class="block font-medium text-sm text-gray-700" for="category">Category</label>
                            <select class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" id="category" name="category">
                                <option value="">All</option>
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
                                <option value="">All</option>
                                <option value="1" @selected(($filters['vip'] ?? '') === '1')>Yes</option>
                                <option value="0" @selected(($filters['vip'] ?? '') === '0')>No</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block font-medium text-sm text-gray-700" for="manager_id">Responsible manager</label>
                            <select class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" id="manager_id" name="manager_id">
                                <option value="">All</option>
                                @foreach ($managers as $manager)
                                    <option value="{{ $manager->id }}" @selected(($filters['manager_id'] ?? '') == $manager->id)>
                                        {{ $manager->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pt-6">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Apply
                            </button>
                        </div>
                    </form>

                    <div class="overflow-x-auto w-full">
                        <table class="w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">VIP</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Responsible manager</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($clients as $client)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $client->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $client->category ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $client->is_vip ? 'Yes' : 'No' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $client->manager?->name ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $client->status }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            <div class="flex items-center gap-3">
                                                <a href="{{ route('orders.clients.edit', $client) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    Edit
                                                </a>
                                                <form method="POST" action="{{ route('orders.clients.deactivate', $client) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-gray-600 hover:text-gray-900" onclick="return confirm('Deactivate this client?')">
                                                        Deactivate
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                                            No clients found.
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
