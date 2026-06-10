<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Laravel'))</title>
        <link rel="icon" type="image/webp" href="{{ asset('images/favicon.webp') }}">
        @include('partials.pwa')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @auth
            @php
                $unfinishedAutosaves = collect();
                if (\Illuminate\Support\Facades\Schema::hasColumn('order_proposals', 'is_autosaved')) {
                    $unfinishedAutosaves = \App\Models\OrderProposal::query()
                        ->whereNull('deleted_date')
                        ->where('is_autosaved', true)
                        ->where('autosaved_by', Auth::id())
                        ->latest('autosaved_at')
                        ->limit(3)
                        ->get(['id', 'proposal_number', 'autosaved_at']);
                }
            @endphp
            @if($unfinishedAutosaves->isNotEmpty())
                <div class="fixed right-5 top-5 z-[14000] max-w-md rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 shadow-lg">
                    <div class="font-semibold">Вами створено прорахунки, які були автоматично збережені без підтвердження:</div>
                    <div class="mt-2 space-y-1">
                        @foreach($unfinishedAutosaves as $autosaveProposal)
                            <a href="{{ route('orders.proposals.show', $autosaveProposal) }}" class="block text-indigo-700 hover:text-indigo-900">
                                {{ $autosaveProposal->proposal_number }}
                                @if($autosaveProposal->autosaved_at)
                                    <span class="text-amber-800">({{ $autosaveProposal->autosaved_at->copy()->timezone('Europe/Kiev')->format('d.m.Y H:i') }})</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endauth
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
