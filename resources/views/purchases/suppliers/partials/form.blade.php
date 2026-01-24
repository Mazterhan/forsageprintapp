<div class="space-y-8">
    <div>
        <h3 class="text-lg font-semibold text-gray-800">Basics</h3>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="name" :value="__('Legal/Brand Name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $supplier?->name) }}" required />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>
            <div>
                <x-input-label for="short_name" :value="__('Short Name')" />
                <x-text-input id="short_name" name="short_name" type="text" class="mt-1 block w-full" value="{{ old('short_name', $supplier?->short_name) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('short_name')" />
            </div>
            <div>
                <x-input-label for="code" :value="__('Internal Code')" />
                <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" value="{{ old('code', $supplier?->code) }}" required />
                <x-input-error class="mt-2" :messages="$errors->get('code')" />
            </div>
            <div>
                <x-input-label for="status" :value="__('Status')" />
                <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="active" @selected(old('status', $supplier?->status) === 'active')>active</option>
                    <option value="paused" @selected(old('status', $supplier?->status) === 'paused')>paused</option>
                    <option value="blocked" @selected(old('status', $supplier?->status) === 'blocked')>blocked</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('status')" />
            </div>
            <div>
                <x-input-label for="type" :value="__('Type')" />
                <x-text-input id="type" name="type" type="text" class="mt-1 block w-full" value="{{ old('type', $supplier?->type) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('type')" />
            </div>
            <div>
                <x-input-label for="category" :value="__('Category')" />
                <x-text-input id="category" name="category" type="text" class="mt-1 block w-full" value="{{ old('category', $supplier?->category) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('category')" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="notes" :value="__('Notes')" />
                <textarea id="notes" name="notes" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="3">{{ old('notes', $supplier?->notes) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('notes')" />
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-lg font-semibold text-gray-800">Contacts</h3>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="contact_name" :value="__('Contact Person')" />
                <x-text-input id="contact_name" name="contact_name" type="text" class="mt-1 block w-full" value="{{ old('contact_name', $supplier?->contact_name) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('contact_name')" />
            </div>
            <div>
                <x-input-label for="contact_role" :value="__('Role/Department')" />
                <x-text-input id="contact_role" name="contact_role" type="text" class="mt-1 block w-full" value="{{ old('contact_role', $supplier?->contact_role) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('contact_role')" />
            </div>
            <div>
                <x-input-label for="phones" :value="__('Phones')" />
                <x-text-input id="phones" name="phones" type="text" class="mt-1 block w-full" value="{{ old('phones', $supplier?->phones) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('phones')" />
            </div>
            <div>
                <x-input-label for="emails" :value="__('Emails')" />
                <x-text-input id="emails" name="emails" type="text" class="mt-1 block w-full" value="{{ old('emails', $supplier?->emails) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('emails')" />
            </div>
            <div>
                <x-input-label for="messengers" :value="__('Messengers')" />
                <x-text-input id="messengers" name="messengers" type="text" class="mt-1 block w-full" value="{{ old('messengers', $supplier?->messengers) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('messengers')" />
            </div>
            <div>
                <x-input-label for="website" :value="__('Website')" />
                <x-text-input id="website" name="website" type="text" class="mt-1 block w-full" value="{{ old('website', $supplier?->website) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('website')" />
            </div>
            <div>
                <x-input-label for="work_hours" :value="__('Work Hours')" />
                <x-text-input id="work_hours" name="work_hours" type="text" class="mt-1 block w-full" value="{{ old('work_hours', $supplier?->work_hours) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('work_hours')" />
            </div>
            <div>
                <x-input-label for="portals" :value="__('Portals/Links')" />
                <x-text-input id="portals" name="portals" type="text" class="mt-1 block w-full" value="{{ old('portals', $supplier?->portals) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('portals')" />
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-lg font-semibold text-gray-800">Addresses and Logistics</h3>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="warehouse_address" :value="__('Warehouse Address')" />
                <x-text-input id="warehouse_address" name="warehouse_address" type="text" class="mt-1 block w-full" value="{{ old('warehouse_address', $supplier?->warehouse_address) }}" />
            </div>
            <div>
                <x-input-label for="pickup_address" :value="__('Pickup Address')" />
                <x-text-input id="pickup_address" name="pickup_address" type="text" class="mt-1 block w-full" value="{{ old('pickup_address', $supplier?->pickup_address) }}" />
            </div>
            <div>
                <x-input-label for="region" :value="__('City/Region')" />
                <x-text-input id="region" name="region" type="text" class="mt-1 block w-full" value="{{ old('region', $supplier?->region) }}" />
            </div>
            <div>
                <x-input-label for="delivery_terms" :value="__('Delivery Terms')" />
                <x-text-input id="delivery_terms" name="delivery_terms" type="text" class="mt-1 block w-full" value="{{ old('delivery_terms', $supplier?->delivery_terms) }}" />
            </div>
            <div>
                <x-input-label for="delivery_time" :value="__('Delivery Time')" />
                <x-text-input id="delivery_time" name="delivery_time" type="text" class="mt-1 block w-full" value="{{ old('delivery_time', $supplier?->delivery_time) }}" />
            </div>
            <div>
                <x-input-label for="warehouse_contacts" :value="__('Warehouse Contacts')" />
                <x-text-input id="warehouse_contacts" name="warehouse_contacts" type="text" class="mt-1 block w-full" value="{{ old('warehouse_contacts', $supplier?->warehouse_contacts) }}" />
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-lg font-semibold text-gray-800">Legal Details</h3>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="legal_entity" :value="__('Legal Entity')" />
                <x-text-input id="legal_entity" name="legal_entity" type="text" class="mt-1 block w-full" value="{{ old('legal_entity', $supplier?->legal_entity) }}" />
            </div>
            <div>
                <x-input-label for="tax_id" :value="__('Tax ID')" />
                <x-text-input id="tax_id" name="tax_id" type="text" class="mt-1 block w-full" value="{{ old('tax_id', $supplier?->tax_id) }}" />
            </div>
            <div>
                <x-input-label for="vat_status" :value="__('VAT Status')" />
                <x-text-input id="vat_status" name="vat_status" type="text" class="mt-1 block w-full" value="{{ old('vat_status', $supplier?->vat_status) }}" />
            </div>
            <div>
                <x-input-label for="registration_address" :value="__('Registration Address')" />
                <x-text-input id="registration_address" name="registration_address" type="text" class="mt-1 block w-full" value="{{ old('registration_address', $supplier?->registration_address) }}" />
            </div>
            <div>
                <x-input-label for="bank_iban" :value="__('Bank / IBAN')" />
                <x-text-input id="bank_iban" name="bank_iban" type="text" class="mt-1 block w-full" value="{{ old('bank_iban', $supplier?->bank_iban) }}" />
            </div>
            <div>
                <x-input-label for="bank_mfo" :value="__('MFO')" />
                <x-text-input id="bank_mfo" name="bank_mfo" type="text" class="mt-1 block w-full" value="{{ old('bank_mfo', $supplier?->bank_mfo) }}" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="legal_address" :value="__('Legal Address')" />
                <x-text-input id="legal_address" name="legal_address" type="text" class="mt-1 block w-full" value="{{ old('legal_address', $supplier?->legal_address) }}" />
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-lg font-semibold text-gray-800">Finance and Terms</h3>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="currency" :value="__('Currency')" />
                <x-text-input id="currency" name="currency" type="text" class="mt-1 block w-full" value="{{ old('currency', $supplier?->currency) }}" />
            </div>
            <div>
                <x-input-label for="default_discount" :value="__('Default Discount %')" />
                <x-text-input id="default_discount" name="default_discount" type="text" class="mt-1 block w-full" value="{{ old('default_discount', $supplier?->default_discount) }}" />
            </div>
            <div>
                <x-input-label for="payment_terms" :value="__('Payment Terms')" />
                <x-text-input id="payment_terms" name="payment_terms" type="text" class="mt-1 block w-full" value="{{ old('payment_terms', $supplier?->payment_terms) }}" />
            </div>
            <div>
                <x-input-label for="min_order" :value="__('Min Order')" />
                <x-text-input id="min_order" name="min_order" type="text" class="mt-1 block w-full" value="{{ old('min_order', $supplier?->min_order) }}" />
            </div>
            <div>
                <x-input-label for="credit_limit" :value="__('Credit Limit')" />
                <x-text-input id="credit_limit" name="credit_limit" type="text" class="mt-1 block w-full" value="{{ old('credit_limit', $supplier?->credit_limit) }}" />
            </div>
            <div>
                <x-input-label for="return_terms" :value="__('Return Terms')" />
                <x-text-input id="return_terms" name="return_terms" type="text" class="mt-1 block w-full" value="{{ old('return_terms', $supplier?->return_terms) }}" />
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-lg font-semibold text-gray-800">Contract</h3>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="contract_number" :value="__('Contract Number')" />
                <x-text-input id="contract_number" name="contract_number" type="text" class="mt-1 block w-full" value="{{ old('contract_number', $supplier?->contract_number) }}" />
            </div>
            <div>
                <x-input-label for="contract_date" :value="__('Contract Date')" />
                <x-text-input id="contract_date" name="contract_date" type="date" class="mt-1 block w-full" value="{{ old('contract_date', optional($supplier?->contract_date)->format('Y-m-d')) }}" />
            </div>
            <div>
                <x-input-label for="contract_status" :value="__('Contract Status')" />
                <x-text-input id="contract_status" name="contract_status" type="text" class="mt-1 block w-full" value="{{ old('contract_status', $supplier?->contract_status) }}" />
            </div>
        </div>
    </div>
</div>
