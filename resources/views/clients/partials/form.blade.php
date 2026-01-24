<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700 uppercase">Client basics</h3>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="code">Client code</label>
            <input id="code" type="text" class="border-gray-300 rounded-md shadow-sm mt-1 block w-full bg-gray-100" value="{{ $client->code ?? 'Generated after save' }}" readonly>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="name">Name</label>
            <input id="name" name="name" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('name', $client->name ?? '') }}" required>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="type">Client type</label>
            <select id="type" name="type" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                <option value="">Select</option>
                <option value="individual" @selected(old('type', $client->type ?? '') === 'individual')>Individual</option>
                <option value="sole_proprietor" @selected(old('type', $client->type ?? '') === 'sole_proprietor')>Sole proprietor</option>
                <option value="company" @selected(old('type', $client->type ?? '') === 'company')>Company</option>
            </select>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="status">Status</label>
            <select id="status" name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                <option value="active" @selected(old('status', $client->status ?? 'active') === 'active')>Active</option>
                <option value="paused" @selected(old('status', $client->status ?? '') === 'paused')>Paused</option>
                <option value="blocked" @selected(old('status', $client->status ?? '') === 'blocked')>Blocked</option>
            </select>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="category">Category</label>
            <input id="category" name="category" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('category', $client->category ?? '') }}">
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="is_vip" value="0">
            <input id="is_vip" name="is_vip" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm" @checked(old('is_vip', $client->is_vip ?? false))>
            <label for="is_vip" class="text-sm text-gray-700">VIP</label>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="tags">Tags</label>
            <input id="tags" name="tags" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('tags', $client->tags ?? '') }}">
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">{{ old('notes', $client->notes ?? '') }}</textarea>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="manager_id">Responsible manager</label>
            <select id="manager_id" name="manager_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                <option value="">Select</option>
                @foreach ($managers as $manager)
                    <option value="{{ $manager->id }}" @selected(old('manager_id', $client->manager_id ?? '') == $manager->id)>
                        {{ $manager->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700 uppercase">Contacts</h3>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="contact_name">Contact person</label>
            <input id="contact_name" name="contact_name" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('contact_name', $client->contact_name ?? '') }}">
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="phones">Phone(s)</label>
            <input id="phones" name="phones" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('phones', $client->phones ?? '') }}">
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="emails">Email(s)</label>
            <input id="emails" name="emails" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('emails', $client->emails ?? '') }}">
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="messengers">Messenger(s)</label>
            <input id="messengers" name="messengers" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('messengers', $client->messengers ?? '') }}">
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="source">Source</label>
            <input id="source" name="source" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('source', $client->source ?? '') }}">
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700 uppercase">Delivery</h3>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="delivery_address">Primary delivery address</label>
            <input id="delivery_address" name="delivery_address" type="text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" value="{{ old('delivery_address', $client->delivery_address ?? '') }}">
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="delivery_notes">Delivery notes</label>
            <textarea id="delivery_notes" name="delivery_notes" rows="3" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">{{ old('delivery_notes', $client->delivery_notes ?? '') }}</textarea>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="delivery_addresses">Delivery addresses list</label>
            <textarea id="delivery_addresses" name="delivery_addresses" rows="3" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">{{ old('delivery_addresses', $client->delivery_addresses ?? '') }}</textarea>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700 uppercase">Audit</h3>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="created_by">Created by</label>
            <input id="created_by" type="text" class="border-gray-300 rounded-md shadow-sm mt-1 block w-full bg-gray-100" value="{{ $client->createdBy->name ?? '-' }}" readonly>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="updated_by">Updated by</label>
            <input id="updated_by" type="text" class="border-gray-300 rounded-md shadow-sm mt-1 block w-full bg-gray-100" value="{{ $client->updatedBy->name ?? '-' }}" readonly>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="created_at">Created at</label>
            <input id="created_at" type="text" class="border-gray-300 rounded-md shadow-sm mt-1 block w-full bg-gray-100" value="{{ optional(optional($client)->created_at)->format('Y-m-d H:i') ?? '-' }}" readonly>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="updated_at">Updated at</label>
            <input id="updated_at" type="text" class="border-gray-300 rounded-md shadow-sm mt-1 block w-full bg-gray-100" value="{{ optional(optional($client)->updated_at)->format('Y-m-d H:i') ?? '-' }}" readonly>
        </div>
        <div>
            <label class="block font-medium text-sm text-gray-700" for="last_order_at">Last order at</label>
            <input id="last_order_at" type="text" class="border-gray-300 rounded-md shadow-sm mt-1 block w-full bg-gray-100" value="{{ optional(optional($client)->last_order_at)->format('Y-m-d H:i') ?? '-' }}" readonly>
        </div>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
    <h3 class="text-sm font-semibold text-gray-700 uppercase mb-4">Orders</h3>
    <div class="text-sm text-gray-500">
        {{ __('Orders table will be added in the next iteration.') }}
    </div>
</div>
