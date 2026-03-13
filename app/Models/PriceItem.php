<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_code',
        'name',
        'model_type',
        'category',
        'material_type',
        'internal_name',
        'service_price',
        'purchase_price',
        'measurement_unit',
        'comment',
        'for_customer_material',
        'width_m',
        'length_m',
        'thickness_mm',
        'is_active',
        'visible',
    ];

    protected $casts = [
        'service_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'width_m' => 'decimal:2',
        'length_m' => 'decimal:2',
        'thickness_mm' => 'decimal:2',
        'for_customer_material' => 'boolean',
        'is_active' => 'boolean',
        'visible' => 'boolean',
    ];

    public function histories(): HasMany
    {
        return $this->hasMany(PriceItemHistory::class)->latest('created_at');
    }
}
