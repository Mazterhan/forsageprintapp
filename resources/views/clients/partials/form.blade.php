<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700 uppercase">Основні дані замовника</h3>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="code">Код замовника</label>
            <input id="code" type="text" class="border-gray-300 rounded-md shadow-sm mt-1 block w-full bg-gray-100" value="{{ $client->code ?? 'Буде згенеровано після збереження' }}" readonly>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="name">Назва</label>
            <input id="name" name="name" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('name', $client->name ?? '') }}" required>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="type">Тип замовника</label>
            <select id="type" name="type" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                <option value="">Оберіть</option>
                <option value="individual" @selected(old('type', $client->type ?? '') === 'individual')>Фізична особа</option>
                <option value="sole_proprietor" @selected(old('type', $client->type ?? '') === 'sole_proprietor')>ФОП</option>
                <option value="company" @selected(old('type', $client->type ?? '') === 'company')>Юридична особа</option>
            </select>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="status">Статус</label>
            <select id="status" name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                <option value="active" @selected(old('status', $client->status ?? 'active') === 'active')>Активний</option>
                <option value="paused" @selected(old('status', $client->status ?? '') === 'paused')>На паузі</option>
                <option value="blocked" @selected(old('status', $client->status ?? '') === 'blocked')>Заблокований</option>
            </select>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="category">Категорія</label>
            <input id="category" name="category" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('category', $client->category ?? '') }}">
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="price_type">Прайс</label>
            <select id="price_type" name="price_type" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" required>
                <option value="">Оберіть</option>
                <option value="retail" @selected(old('price_type', $client->price_type ?? '') === 'retail')>Роздрібна ціна</option>
                <option value="wholesale" @selected(old('price_type', $client->price_type ?? '') === 'wholesale')>Оптова ціна</option>
                <option value="vip" @selected(old('price_type', $client->price_type ?? '') === 'vip')>VIP ціна</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="is_vip" value="0">
            <input id="is_vip" name="is_vip" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm" @checked(old('is_vip', $client->is_vip ?? false))>
            <label for="is_vip" class="text-sm text-gray-700">VIP</label>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="tags">Теги</label>
            <input id="tags" name="tags" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('tags', $client->tags ?? '') }}">
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="notes">Примітки</label>
            <textarea id="notes" name="notes" rows="3" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">{{ old('notes', $client->notes ?? '') }}</textarea>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="manager_id">Відповідальний менеджер</label>
            <select id="manager_id" name="manager_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                <option value="">Оберіть</option>
                @foreach ($managers as $manager)
                    <option value="{{ $manager->id }}" @selected(old('manager_id', $client->manager_id ?? '') == $manager->id)>
                        {{ $manager->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700 uppercase">Контакти</h3>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="contact_name">Контактна особа</label>
            <input id="contact_name" name="contact_name" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('contact_name', $client->contact_name ?? '') }}">
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="phones">Телефон(и)</label>
            <input id="phones" name="phones" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('phones', $client->phones ?? '') }}">
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="emails">Email(и)</label>
            <input id="emails" name="emails" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('emails', $client->emails ?? '') }}">
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="messengers">Месенджер(и)</label>
            <input id="messengers" name="messengers" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('messengers', $client->messengers ?? '') }}">
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="source">Джерело</label>
            <input id="source" name="source" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('source', $client->source ?? '') }}">
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700 uppercase">Доставка</h3>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="delivery_address">Основна адреса доставки</label>
            <input id="delivery_address" name="delivery_address" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('delivery_address', $client->delivery_address ?? '') }}">
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="delivery_notes">Коментар до доставки</label>
            <textarea id="delivery_notes" name="delivery_notes" rows="3" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">{{ old('delivery_notes', $client->delivery_notes ?? '') }}</textarea>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="delivery_addresses">Список адрес доставки</label>
            <textarea id="delivery_addresses" name="delivery_addresses" rows="3" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">{{ old('delivery_addresses', $client->delivery_addresses ?? '') }}</textarea>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700 uppercase">Службові поля</h3>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="created_by">Створив</label>
            <input id="created_by" type="text" class="border-gray-300 rounded-md shadow-sm mt-1 block w-full bg-gray-100" value="{{ $client->createdBy->name ?? '-' }}" readonly>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="updated_by">Оновив</label>
            <input id="updated_by" type="text" class="border-gray-300 rounded-md shadow-sm mt-1 block w-full bg-gray-100" value="{{ $client->updatedBy->name ?? '-' }}" readonly>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="created_at">Дата створення</label>
            <input id="created_at" type="text" class="border-gray-300 rounded-md shadow-sm mt-1 block w-full bg-gray-100" value="{{ optional(optional($client)->created_at)->format('Y-m-d H:i') ?? '-' }}" readonly>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="updated_at">Дата оновлення</label>
            <input id="updated_at" type="text" class="border-gray-300 rounded-md shadow-sm mt-1 block w-full bg-gray-100" value="{{ optional(optional($client)->updated_at)->format('Y-m-d H:i') ?? '-' }}" readonly>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="last_order_at">Останнє замовлення</label>
            <input id="last_order_at" type="text" class="border-gray-300 rounded-md shadow-sm mt-1 block w-full bg-gray-100" value="{{ optional(optional($client)->last_order_at)->format('Y-m-d H:i') ?? '-' }}" readonly>
        </div>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
    <h3 class="text-sm font-semibold text-gray-700 uppercase mb-4">Замовлення</h3>
    <div class="text-sm text-gray-500">
        {{ __('Таблицю замовлень буде додано на наступній ітерації.') }}
    </div>
</div>
