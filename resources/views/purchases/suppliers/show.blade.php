<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Профыль постачальника') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('purchases.suppliers.update', $supplier) }}" class="space-y-8">
                        @csrf
                        @method('PATCH')
                        @include('purchases.suppliers.partials.form', ['supplier' => $supplier])
                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                            <a href="{{ route('purchases.suppliers.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Back') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold text-gray-800">Documents</h3>
                    <form method="POST" action="{{ route('purchases.suppliers.documents.store', $supplier) }}" enctype="multipart/form-data" class="mt-4 flex items-center gap-4">
                        @csrf
                        <input type="file" name="document" class="block text-sm text-gray-700" required />
                        <x-primary-button>{{ __('Upload') }}</x-primary-button>
                    </form>
                    <x-input-error class="mt-2" :messages="$errors->get('document')" />

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Format</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($documents as $document)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            <a href="{{ route('purchases.suppliers.documents.download', $document) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $document->file_name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $document->file_ext }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ optional($document->uploaded_at)->format('Y-m-d') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('No documents uploaded.') }}
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
