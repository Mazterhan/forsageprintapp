<x-app-layout>
    @section('title', __('Набір з позицій (FLM)'))
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Набір з позицій (FLM)') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12">
            @if (session('status'))
                <div class="mb-4 text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->has('set_flm'))
                <div class="mb-4 text-sm font-bold text-red-700 bg-red-100 px-4 py-2 rounded">
                    {{ $errors->first('set_flm') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.set-flm.store') }}" id="set-flm-form" class="p-6 text-gray-900 space-y-6">
                    @csrf

                    <div class="space-y-3">
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ __('Спеціальні рулонні матеріали категорії Плівка, доступні лише для типу виробу Сольвентний друк.') }}
                        </h3>

                        <details class="rounded-md border border-gray-200 bg-gray-50">
                            <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-gray-800">
                                {{ __('Правила прорахунку') }}
                            </summary>
                            <div class="space-y-3 border-t border-gray-200 px-4 py-4 text-sm text-gray-700">
                                <p>
                                    Правила прорахунку для таких позицій на сторінці
                                    <a
                                        href="https://ordercalc.forsage-print.com.ua/orders/calculation"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="font-semibold text-indigo-600 hover:text-indigo-800"
                                    >{{ __('Прорахунок замовлення') }}</a>:
                                </p>
                                <ul class="list-disc space-y-1 pl-5">
                                    <li>не відображаються для <code>УФ друк</code> та <code>Чистий матеріал</code>;</li>
                                    <li>блок <code>Ламінування</code> прихований, оскільки відповідна обробка вже врахована в ціні матеріалу;</li>
                                    <li>у формулі сольвентного друку не додається окрема вартість послуги <code>SERV-012</code>;</li>
                                    <li>вартість позиції рахується як: <code>ціна матеріалу × ширина × висота × кількість × коефіцієнт терміновості</code>;</li>
                                    <li>собівартість позиції рахується як: <code>закупівельна ціна матеріалу × ширина × висота × кількість</code>;</li>
                                    <li>доступні послуги: <code>Порізка</code>, <code>Вибірка</code>, <code>Монтажка</code>, <code>Прикатка</code>, <code>Дизайн</code>, <code>Пакування</code>;</li>
                                    <li>для <code>Порізка</code> доступний режим <code>Плотер</code> з мінімальною вартістю <code>50 грн</code>;</li>
                                    <li><code>Люверси</code> та <code>Пропайка</code> недоступні для цих матеріалів.</li>
                                </ul>
                            </div>
                        </details>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-200 text-sm">
                            <thead class="bg-[#FCEEDF]">
                                <tr>
                                    <th class="px-4 py-3 border-b text-left font-semibold text-gray-800">{{ __('Назва позиції') }}</th>
                                    <th class="w-[430px] px-4 py-3 border-b text-right font-semibold text-gray-800">{{ __('Код позиції') }}</th>
                                    <th class="w-[130px] px-4 py-3 border-b text-right font-semibold text-gray-800">{{ __('Дія') }}</th>
                                </tr>
                            </thead>
                            <tbody id="set-flm-rows">
                                @foreach ($selectedRows as $index => $row)
                                    <tr class="set-flm-row odd:bg-[#F9FAFB] even:bg-white">
                                        <td class="px-4 py-3 border-b">
                                            <input
                                                type="text"
                                                value="{{ $row['name'] }}"
                                                readonly
                                                disabled
                                                class="set-flm-name block w-full rounded-md border-gray-300 bg-gray-100 text-gray-700 shadow-sm"
                                            >
                                        </td>
                                        <td class="px-4 py-3 border-b">
                                            <div class="flex justify-end">
                                            <select name="price_item_ids[]" class="set-flm-code block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="width: 400px; min-width: 400px; max-width: 400px;">
                                                <option value="">{{ __('Оберіть код') }}</option>
                                                @foreach ($priceItems as $item)
                                                    <option value="{{ $item['id'] }}" data-code="{{ $item['code'] }}" data-name="{{ $item['name'] }}" @selected((string) ($row['price_item_id'] ?? '') === (string) $item['id'] || (string) ($row['code'] ?? '') === (string) $item['code'])>
                                                        {{ $item['code'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 border-b text-right">
                                            <button type="button" class="remove-set-flm-row inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs text-gray-700 hover:bg-gray-50" style="{{ trim((string) ($row['code'] ?? '')) !== '' || trim((string) ($row['name'] ?? '')) !== '' ? 'display: inline-flex;' : 'display: none;' }}">
                                                {{ __('Видалити') }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="set-flm-row odd:bg-[#F9FAFB] even:bg-white">
                                    <td class="px-4 py-3 border-b">
                                        <input
                                            type="text"
                                            value=""
                                            readonly
                                            disabled
                                            class="set-flm-name block w-full rounded-md border-gray-300 bg-gray-100 text-gray-700 shadow-sm"
                                        >
                                    </td>
                                    <td class="px-4 py-3 border-b">
                                        <div class="flex justify-end">
                                        <select name="price_item_ids[]" class="set-flm-code block rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="width: 400px; min-width: 400px; max-width: 400px;">
                                            <option value="">{{ __('Оберіть код') }}</option>
                                            @foreach ($priceItems as $item)
                                                <option value="{{ $item['id'] }}" data-code="{{ $item['code'] }}" data-name="{{ $item['name'] }}">
                                                    {{ $item['code'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 border-b text-right">
                                        <button type="button" class="remove-set-flm-row inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs text-gray-700 hover:bg-gray-50" style="display: none;">
                                            {{ __('Видалити') }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Зберегти зміни у списку') }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Історія змін') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-200 text-sm">
                            <thead class="bg-[#FCEEDF]">
                                <tr>
                                    <th class="w-[70px] px-4 py-3 border-b text-left font-semibold text-gray-800">№</th>
                                    <th class="w-[180px] px-4 py-3 border-b text-left font-semibold text-gray-800">{{ __('Дата') }}</th>
                                    <th class="w-[220px] px-4 py-3 border-b text-left font-semibold text-gray-800">{{ __('Користувач') }}</th>
                                    <th class="w-[170px] px-4 py-3 border-b text-left font-semibold text-gray-800">{{ __('Позиція') }}</th>
                                    <th class="px-4 py-3 border-b text-left font-semibold text-gray-800">{{ __('Що змінено') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($historyRows as $index => $row)
                                    <tr class="odd:bg-[#F9FAFB] even:bg-white">
                                        <td class="px-4 py-3 border-b">{{ $index + 1 }}</td>
                                        <td class="px-4 py-3 border-b">{{ optional($row['created_at'] ? \Carbon\Carbon::parse($row['created_at']) : null)->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</td>
                                        <td class="px-4 py-3 border-b">{{ $row['user_name'] }}</td>
                                        <td class="px-4 py-3 border-b">
                                            <div class="font-semibold text-gray-800">{{ $row['code'] ?: '-' }}</div>
                                            <div class="text-xs text-gray-500">{{ $row['name'] ?: '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 border-b">{{ $row['summary'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('Історія змін поки порожня.') }}
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

    <script>
        (function () {
            const rowsContainer = document.getElementById('set-flm-rows');
            if (!rowsContainer) {
                return;
            }

            const bindSelect = (select) => {
                select.addEventListener('change', () => {
                    const row = select.closest('.set-flm-row');
                    const nameInput = row?.querySelector('.set-flm-name');
                    const selectedOption = select.options[select.selectedIndex];
                    if (nameInput instanceof HTMLInputElement) {
                        nameInput.value = selectedOption?.dataset?.name || '';
                    }
                    ensureTrailingEmptyRow();
                });
            };

            const buildRow = () => {
                const sourceRow = rowsContainer.querySelector('.set-flm-row');
                const row = sourceRow.cloneNode(true);
                const input = row.querySelector('.set-flm-name');
                const select = row.querySelector('.set-flm-code');
                const button = row.querySelector('.remove-set-flm-row');
                if (input instanceof HTMLInputElement) {
                    input.value = '';
                }
                if (select instanceof HTMLSelectElement) {
                    select.value = '';
                    bindSelect(select);
                }
                if (button instanceof HTMLButtonElement) {
                    button.style.display = 'none';
                    button.disabled = true;
                }
                return row;
            };

            const ensureTrailingEmptyRow = () => {
                const rows = Array.from(rowsContainer.querySelectorAll('.set-flm-row'));
                const lastSelect = rows.at(-1)?.querySelector('.set-flm-code');
                if (lastSelect instanceof HTMLSelectElement && lastSelect.value !== '') {
                    rowsContainer.appendChild(buildRow());
                }
                syncRemoveButtons();
            };

            const syncRemoveButtons = () => {
                rowsContainer.querySelectorAll('.set-flm-row').forEach((row) => {
                    const select = row.querySelector('.set-flm-code');
                    const button = row.querySelector('.remove-set-flm-row');
                    const nameInput = row.querySelector('.set-flm-name');
                    if (!(select instanceof HTMLSelectElement) || !(button instanceof HTMLButtonElement)) {
                        return;
                    }

                    const hasSelectedPosition = select.value !== ''
                        || (nameInput instanceof HTMLInputElement && nameInput.value.trim() !== '');
                    button.style.display = hasSelectedPosition ? 'inline-flex' : 'none';
                    button.disabled = !hasSelectedPosition;
                });
            };

            rowsContainer.querySelectorAll('.set-flm-code').forEach(bindSelect);

            rowsContainer.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement) || !target.classList.contains('remove-set-flm-row')) {
                    return;
                }

                const row = target.closest('.set-flm-row');
                if (!row) {
                    return;
                }

                const nameInput = row.querySelector('.set-flm-name');
                const select = row.querySelector('.set-flm-code');
                const positionName = nameInput instanceof HTMLInputElement && nameInput.value.trim() !== ''
                    ? nameInput.value.trim()
                    : 'обрану позицію';
                const confirmed = window.confirm(
                    `Видалити ${positionName} зі списку?\n\nОстаточне змінення списку буде застосовано тільки після натискання кнопки "Зберегти зміни у списку".`
                );
                if (!confirmed) {
                    return;
                }

                const rows = Array.from(rowsContainer.querySelectorAll('.set-flm-row'));
                if (rows.length <= 1) {
                    if (nameInput instanceof HTMLInputElement) {
                        nameInput.value = '';
                    }
                    if (select instanceof HTMLSelectElement) {
                        select.value = '';
                    }
                    return;
                }

                row.remove();
                ensureTrailingEmptyRow();
            });

            ensureTrailingEmptyRow();
        })();
    </script>
</x-app-layout>
