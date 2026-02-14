<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:individual,sole_proprietor,company'],
            'status' => ['nullable', 'string', 'in:active,paused,blocked'],
            'category' => ['nullable', 'string', 'max:255'],
            'price_type' => ['required', 'string', 'in:retail,wholesale,vip'],
            'is_vip' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phones' => ['nullable', 'string', 'max:255'],
            'emails' => ['nullable', 'string', 'max:255'],
            'messengers' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'delivery_notes' => ['nullable', 'string'],
            'delivery_addresses' => ['nullable', 'string'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
