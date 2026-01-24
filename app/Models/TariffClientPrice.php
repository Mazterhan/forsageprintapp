<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffClientPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'tariff_id',
        'client_category',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }
}
