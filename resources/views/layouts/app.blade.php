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
        <script>
            window.autosaveNotice = function (signature) {
                return {
                    signature,
                    visible: true,
                    dragging: false,
                    dragOffsetX: 0,
                    dragOffsetY: 0,
                    left: null,
                    top: null,

                    get positionStyle() {
                        if (this.left === null || this.top === null) {
                            return 'right: 1.25rem; top: 1.25rem;';
                        }

                        return `left: ${this.left}px; top: ${this.top}px;`;
                    },

                    init() {
                        const dismissedSignature = sessionStorage.getItem('forsageprint-autosave-notice-dismissed');
                        this.visible = dismissedSignature !== this.signature;

                        try {
                            const savedPosition = JSON.parse(localStorage.getItem('forsageprint-autosave-notice-position') || 'null');
                            if (savedPosition && Number.isFinite(savedPosition.left) && Number.isFinite(savedPosition.top)) {
                                this.left = this.clamp(savedPosition.left, 0, window.innerWidth - 280);
                                this.top = this.clamp(savedPosition.top, 0, window.innerHeight - 120);
                            }
                        } catch (error) {
                            // Ignore invalid localStorage payloads.
                        }
                    },

                    close() {
                        this.visible = false;
                        sessionStorage.setItem('forsageprint-autosave-notice-dismissed', this.signature);
                    },

                    startDrag(event) {
                        if (window.innerWidth < 768) {
                            return;
                        }

                        const rect = this.$el.getBoundingClientRect();
                        this.left = rect.left;
                        this.top = rect.top;
                        this.dragOffsetX = event.clientX - rect.left;
                        this.dragOffsetY = event.clientY - rect.top;
                        this.dragging = true;
                    },

                    drag(event) {
                        if (!this.dragging) {
                            return;
                        }

                        const rect = this.$el.getBoundingClientRect();
                        this.left = this.clamp(event.clientX - this.dragOffsetX, 0, window.innerWidth - rect.width);
                        this.top = this.clamp(event.clientY - this.dragOffsetY, 0, window.innerHeight - rect.height);
                    },

                    stopDrag() {
                        if (!this.dragging) {
                            return;
                        }

                        this.dragging = false;
                        localStorage.setItem('forsageprint-autosave-notice-position', JSON.stringify({
                            left: this.left,
                            top: this.top,
                        }));
                    },

                    clamp(value, min, max) {
                        return Math.min(Math.max(value, min), Math.max(min, max));
                    },
                };
            };
        </script>
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
                        ->get(['id', 'public_id', 'proposal_number', 'autosaved_at']);
                }
                $unfinishedAutosavesSignature = $unfinishedAutosaves
                    ->map(fn ($proposal) => $proposal->id.'-'.optional($proposal->autosaved_at)->timestamp)
                    ->implode('|');
            @endphp
            @if($unfinishedAutosaves->isNotEmpty())
                <div
                    x-data="window.autosaveNotice(@js($unfinishedAutosavesSignature))"
                    x-show="visible"
                    x-cloak
                    x-init="init()"
                    @mouseup.window="stopDrag()"
                    @mousemove.window="drag($event)"
                    class="fixed z-[14000] max-w-md rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 shadow-lg"
                    :style="positionStyle"
                >
                    <div
                        class="flex cursor-move items-start justify-between gap-3"
                        @mousedown.prevent="startDrag($event)"
                    >
                        <div class="font-semibold">Вами створено прорахунки, які були автоматично збережені без підтвердження:</div>
                        <button
                            type="button"
                            class="shrink-0 rounded px-1 text-lg leading-none text-amber-900 hover:bg-amber-100"
                            title="Закрити"
                            @mousedown.stop
                            @click="close()"
                        >&times;</button>
                    </div>
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
