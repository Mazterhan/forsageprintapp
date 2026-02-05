<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\TariffCrossLink;

class Tariff extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_code',
        'name',
        'category',
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
        'is_active' => 'boolean',
    ];

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
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
