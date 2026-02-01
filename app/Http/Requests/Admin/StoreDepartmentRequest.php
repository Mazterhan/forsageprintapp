<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'lead_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'categories' => ['array'],
            'categories.*' => ['nullable', 'string', 'max:255'],
            'positions_name' => ['array'],
            'positions_name.*' => ['nullable', 'string', 'max:255'],
            'positions_category_id' => ['array'],
            'positions_category_id.*' => ['nullable', 'integer', 'exists:department_categories,id'],
        ];
    }
}
