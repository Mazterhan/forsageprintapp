<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'supplier_id',
        'external_code',
        'internal_code',
        'name',
        'category',
        'unit',
        'qty',
        'price_raw',
        'price_vat',
        'row_hash',
        'imported_at',
        'is_active',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'price_raw' => 'decimal:4',
        'price_vat' => 'decimal:4',
        'imported_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
