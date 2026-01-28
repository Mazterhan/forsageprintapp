<?php

namespace App\Http\Requests\Purchases;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'vat_mode' => ['required', 'in:vat,novat'],
            'file' => ['required', 'file', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $supplierId = $this->input('supplier_id');
            $supplierName = $this->input('supplier_name');

            if (empty($supplierId) && empty($supplierName)) {
                $validator->errors()->add('supplier_id', 'Виберіть постачальника або введіть ім\'я, вказане вручну.');
            }

            $file = $this->file('file');
            if ($file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $allowed = ['csv', 'xlsx'];
                if (! in_array($extension, $allowed, true)) {
                    $validator->errors()->add('file', 'Файл має бути у форматі CSV або XLSX.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'vat_mode.required' => 'Оберіть, чи вказана ціна з ПДВ.',
            'vat_mode.in' => 'Оберіть коректний режим ПДВ.',
            'file.max' => 'Розмір поля файлу не повинен перевищувати 10240 кілобайт.',
        ];
    }
}
