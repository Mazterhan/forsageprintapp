<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\TariffCrossLink;
use App\Models\ProductGroup;

class Tariff extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_code',
        'name',
        'category',
        'product_group_id',
        'type_class',
        'film_brand_series',
        'roll_width_m',
        'roll_length_m',
        'sheet_thickness_mm',
        'sheet_width_mm',
        'sheet_length_mm',
        'color',
        'finish',
        'special_effect',
        'liner',
        'double_sided',
        'subcontractor_id',
        'purchase_price',
        'sale_price',
        'wholesale_price',
        'urgent_price',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:4',
        'sale_price' => 'decimal:4',
        'wholesale_price' => 'decimal:2',
        'urgent_price' => 'decimal:2',
        'roll_width_m' => 'decimal:2',
        'roll_length_m' => 'decimal:2',
        'sheet_thickness_mm' => 'decimal:2',
        'sheet_width_mm' => 'decimal:3',
        'sheet_length_mm' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function productGroup(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class);
    }

    public function clientPrices(): HasMany
    {
        return $this->hasMany(TariffClientPrice::class);
    }

    public function crossLinks(): HasMany
    {
        return $this->hasMany(TariffCrossLink::class, 'parent_tariff_id');
    }
}
