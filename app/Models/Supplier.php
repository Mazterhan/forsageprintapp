<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'code',
        'status',
        'type',
        'category',
        'notes',
        'contact_name',
        'contact_role',
        'phones',
        'emails',
        'messengers',
        'website',
        'work_hours',
        'portals',
        'warehouse_address',
        'pickup_address',
        'region',
        'delivery_terms',
        'delivery_time',
        'warehouse_contacts',
        'legal_entity',
        'tax_id',
        'vat_status',
        'registration_address',
        'bank_iban',
        'bank_mfo',
        'legal_address',
        'currency',
        'default_discount',
        'payment_terms',
        'min_order',
        'credit_limit',
        'return_terms',
        'contract_number',
        'contract_date',
        'contract_status',
        'contract_file_path',
        'is_active',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'default_discount' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class);
    }
}
