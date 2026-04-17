<x-app-layout>
    @section('title', __('Збережені заявки'))
    <x-slot name="header">
        <div x-data="{}" class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Збережені заявки') }}</h2>
                <button
                    x-show="!($store.proposalManage && $store.proposalManage.mode)"
                    x-cloak
                    type="button"
                    @click="$store.proposalManage && $store.proposalManage.enterManageMode()"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50"
                >
                    Керування списком заявок
                </button>
            </div>
            <a href="{{ route('orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('Повернутись до замовлень') }}
            </a>
        </div>
    </x-slot>

    @php
        $nextDir = fn (string $column) => ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
        $sortLink = fn (string $column) => route('orders.proposals', array_merge(request()->query(), ['sort' => $column, 'direction' => $nextDir($column)]));
        $canManageProposals = in_array((string) (auth()->user()->role ?? ''), ['admin', 'manager'], true);
        $proposalRowsForManage = $proposals->getCollection()->map(function ($proposal) {
            $date = ((int) ($proposal->corrections_count ?? 0)) > 0 ? $proposal->updated_at : $proposal->created_at;

            return [
                'id' => (int) $proposal->id,
                'date' => optional($date)->format('d.m.Y H:i'),
                'number' => (string) $proposal->proposal_number,
                'client_name' => (string) ($proposal->client_name ?: '—'),
                'total_cost_formatted' => number_format((float) $proposal->total_cost, 2, '.', ' '),
            ];
        })->values()->all();
    @endphp

    <style>
        [x-cloak] { display: none !important; }

        .proposal-table thead tr {
            background-color: #FCEEDF;
        }

        .proposal-row {
            transition: background-color 0.5s ease, background-image 0.5s ease;
        }

        .proposal-row td {
            background: transparent;
            transition: border-color 0.5s ease;
        }

        .proposal-row.row-alt {
            background-color: #F9FAFB;
        }

        .proposal-row.row-base {
            background-color: #FFFFFF;
        }

        .proposal-row:hover {
            background-image: linear-gradient(90deg, #e9f7f7 0%, #D8F1F2 100%);
            background-color: #D8F1F2;
        }

        .proposal-row.is-active td {
            border-top: 2px solid #C3C3C3 !important;
            border-bottom: 2px solid #C3C3C3 !important;
        }

        .proposal-row.is-active td:first-child {
            border-left: 2px solid #C3C3C3 !important;
        }

        .proposal-row.is-active td:last-child {
            border-right: 2px solid #C3C3C3 !important;
        }

        .delete-modal-table th,
        .delete-modal-table td {
            border-right: 1px solid #D1D5DB;
        }

        .delete-modal-table th:last-child,
        .delete-modal-table td:last-child {
            border-right: none;
        }
    </style>

    <div class="py-12">
        <div x-data="{}" class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full">
                <div class="p-6 text-gray-900">
                    @if (session('status'))
                        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @if($canManageProposals)
                        <div x-show="$store.proposalManage && $store.proposalManage.mode" x-cloak class="mb-4 flex items-center justify-between gap-4">
                            <button
                                type="button"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50"
                            >
                                Об'єднати заявки
                            </button>
                            <button
                                type="button"
                                @click="$store.proposalManage && $store.proposalManage.openDeleteModal()"
                                :disabled="!($store.proposalManage && $store.proposalManage.canDelete())"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-semibold text-white disabled:opacity-50 disabled:cursor-not-allowed"
                                style="background-color: #DC2626;"
                            >
                                Видалити заявки
                            </button>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="proposal-table min-w-full text-sm border border-gray-200">
                            <thead>
                                <tr>
                                    @if($canManageProposals)
                                        <th x-show="$store.proposalManage && $store.proposalManage.mode" x-cloak class="px-4 py-3 border-b text-left text-[14px]">#</th>
                                    @endif
                                    <th class="px-4 py-3 border-b text-left text-[14px]">
                                        <a class="inline-flex items-center gap-1" href="{{ $sortLink('date') }}">
                                            Дата
                                            @if ($sort === 'date')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 border-b text-left text-[14px]">
                                        <a class="inline-flex items-center gap-1" href="{{ $sortLink('number') }}">
                                            Номер заявки
                                            @if ($sort === 'number')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 border-b text-left text-[14px]">
                                        <a class="inline-flex items-center gap-1" href="{{ $sortLink('client') }}">
                                            Ім'я замовника
                                            @if ($sort === 'client')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 border-b text-left text-[14px]">
                                        <a class="inline-flex items-center gap-1" href="{{ $sortLink('user') }}">
                                            Користувач
                                            @if ($sort === 'user')
                                                <span class="text-gray-600">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 border-b text-right text-[14px]">Вартість</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($proposals as $proposal)
                                    <tr class="proposal-row {{ $loop->odd ? 'row-alt' : 'row-base' }}" tabindex="0">
                                        @if($canManageProposals)
                                            <td x-show="$store.proposalManage && $store.proposalManage.mode" x-cloak class="px-4 py-3 border-b">
                                                <input
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                    :checked="$store.proposalManage && $store.proposalManage.isSelected({{ (int) $proposal->id }})"
                                                    @change="$store.proposalManage && $store.proposalManage.toggle({{ (int) $proposal->id }}, $event.target.checked)"
                                                >
                                            </td>
                                        @endif
                                        <td class="px-4 py-3 border-b">
                                            {{ optional(((int) ($proposal->corrections_count ?? 0)) > 0 ? $proposal->updated_at : $proposal->created_at)->format('d.m.Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3 border-b">
                                            <a href="{{ route('orders.proposals.show', $proposal) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $proposal->proposal_number }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 border-b">{{ $proposal->client_name ?: '—' }}</td>
                                        <td class="px-4 py-3 border-b">{{ $proposal->user?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 border-b text-right">{{ number_format((float)$proposal->total_cost, 2, '.', ' ') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $canManageProposals ? 6 : 5 }}" class="px-4 py-8 text-center text-gray-500">Заявки ще не збережено.</td>
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

    @if($canManageProposals)
        <div
            x-data="{}"
            x-show="$store.proposalManage && $store.proposalManage.deleteModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        >
            <div
                @click.outside="$store.proposalManage && $store.proposalManage.closeDeleteModal()"
                class="rounded-lg shadow-lg border border-gray-300 p-6"
                style="background-color: #E0E0E0; width: 608px; max-width: calc(100vw - 2rem);"
            >
                <div class="text-center text-base font-semibold mb-3">
                    Увага! Ви намагаєтеся видалити заявки.
                </div>
                <div class="text-sm text-gray-700 mb-4">
                    <p>Після підтвердження обрані заявки буде видалено.</p>
                    <p>Усі пов'язані з ними дані стануть недоступними для подальшого перегляду та обробки.</p>
                </div>

                <div class="mb-5 overflow-hidden rounded border border-gray-300 bg-white">
                    <table class="delete-modal-table w-full text-sm bg-white">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 text-left border-b border-r border-gray-200">№</th>
                                <th class="px-3 py-2 text-left border-b border-r border-gray-200">Дата</th>
                                <th class="px-3 py-2 text-left border-b border-r border-gray-200">Номер заявки</th>
                                <th class="px-3 py-2 text-left border-b border-r border-gray-200">Ім'я замовника</th>
                                <th class="px-3 py-2 text-right border-b">Вартість</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in ($store.proposalManage ? $store.proposalManage.selectedRows : [])" :key="row.id">
                                <tr class="border-b border-gray-100 last:border-b-0">
                                    <td class="px-3 py-2 border-r border-gray-200" x-text="index + 1"></td>
                                    <td class="px-3 py-2 border-r border-gray-200" x-text="row.date"></td>
                                    <td class="px-3 py-2 border-r border-gray-200" x-text="row.number"></td>
                                    <td class="px-3 py-2 border-r border-gray-200" x-text="row.client_name"></td>
                                    <td class="px-3 py-2 text-right" x-text="row.total_cost_formatted"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-center items-center gap-3">
                    <button
                        type="button"
                        @click="$store.proposalManage && $store.proposalManage.submitDelete()"
                        class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-white"
                        style="background-color: #DC2626;"
                    >
                        Підтвердити видалення
                    </button>
                    <button
                        type="button"
                        @click="$store.proposalManage && $store.proposalManage.closeDeleteModal()"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50"
                    >
                        Скасувати
                    </button>
                </div>

                <form id="proposal-bulk-delete-form" method="POST" action="{{ route('orders.proposals.deactivate') }}" class="hidden">
                    @csrf
                    <template x-for="id in ($store.proposalManage ? $store.proposalManage.selectedIds : [])" :key="`delete-${id}`">
                        <input type="hidden" name="proposal_ids[]" :value="id">
                    </template>
                </form>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('alpine:init', () => {
            const proposals = @json($proposalRowsForManage, JSON_UNESCAPED_UNICODE);
            const canManage = @json($canManageProposals);

            Alpine.store('proposalManage', {
                mode: false,
                deleteModalOpen: false,
                canManage,
                proposals,
                selected: {},

                enterManageMode() {
                    if (!this.canManage) {
                        return;
                    }
                    this.mode = true;
                    this.selected = {};
                    this.deleteModalOpen = false;
                },

                toggle(id, checked) {
                    if (!this.mode || !this.canManage) {
                        return;
                    }

                    const normalizedId = Number(id);
                    if (Number.isNaN(normalizedId)) {
                        return;
                    }

                    if (checked) {
                        this.selected[normalizedId] = true;
                    } else {
                        delete this.selected[normalizedId];
                    }
                },

                isSelected(id) {
                    return !!this.selected[Number(id)];
                },

                get selectedIds() {
                    return Object.keys(this.selected)
                        .map((id) => Number(id))
                        .filter((id) => !Number.isNaN(id));
                },

                get selectedRows() {
                    const ids = new Set(this.selectedIds);
                    return this.proposals.filter((row) => ids.has(Number(row.id)));
                },

                canDelete() {
                    return this.selectedIds.length > 0;
                },

                openDeleteModal() {
                    if (!this.canDelete()) {
                        return;
                    }
                    this.deleteModalOpen = true;
                },

                closeDeleteModal() {
                    this.deleteModalOpen = false;
                },

                submitDelete() {
                    if (!this.canDelete()) {
                        return;
                    }
                    const form = document.getElementById('proposal-bulk-delete-form');
                    if (form) {
                        form.submit();
                    }
                },
            });
        });

        (function () {
            const rows = Array.from(document.querySelectorAll('.proposal-row'));
            rows.forEach((row) => {
                row.addEventListener('click', () => {
                    rows.forEach((item) => item.classList.remove('is-active'));
                    row.classList.add('is-active');
                });
            });
        })();
    </script>
</x-app-layout>
