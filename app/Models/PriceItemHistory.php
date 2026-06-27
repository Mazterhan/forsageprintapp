<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceItemHistory extends Model
{
    use HasFactory, HasPublicId;

    public $timestamps = false;

    protected $fillable = [
        'price_item_id',
        'service_price',
        'purchase_price',
        'markup_percent',
        'user_id',
    ];

    protected $casts = [
        'service_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'markup_percent' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function priceItem(): BelongsTo
    {
        return $this->belongsTo(PriceItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
