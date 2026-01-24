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
            'file' => ['required', 'file', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $supplierId = $this->input('supplier_id');
            $supplierName = $this->input('supplier_name');

            if (empty($supplierId) && empty($supplierName)) {
                $validator->errors()->add('supplier_id', 'Select a supplier or enter a manual name.');
            }

            $file = $this->file('file');
            if ($file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $allowed = ['csv', 'xlsx'];
                if (! in_array($extension, $allowed, true)) {
                    $validator->errors()->add('file', 'The file must be CSV or XLSX.');
                }
            }
        });
    }
}
