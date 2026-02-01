<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $departmentId = $this->route('department')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($departmentId)],
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
