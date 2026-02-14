<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_code',
        'external_code',
        'name',
        'category',
        'unit',
        'supplier_id',
        'subcontractor_id',
        'import_price',
        'markup_percent',
        'markup_price',
        'markup_wholesale_percent',
        'wholesale_price',
        'markup_vip_percent',
        'vip_price',
        'last_changed_at',
        'last_imported_at',
        'is_active',
    ];

    protected $casts = [
        'import_price' => 'decimal:4',
        'markup_percent' => 'decimal:2',
        'markup_price' => 'decimal:4',
        'markup_wholesale_percent' => 'decimal:2',
        'wholesale_price' => 'decimal:4',
        'markup_vip_percent' => 'decimal:2',
        'vip_price' => 'decimal:4',
        'last_changed_at' => 'datetime',
        'last_imported_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
