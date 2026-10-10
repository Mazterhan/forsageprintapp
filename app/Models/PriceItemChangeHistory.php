<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceItemChangeHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'price_item_id',
        'field',
        'old_value',
        'new_value',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function priceItem(): BelongsTo
    {
        return $this->belongsTo(PriceItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
