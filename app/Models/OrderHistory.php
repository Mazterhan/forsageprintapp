<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'user_id',
        'operation_type',
        'item_index',
        'field_name',
        'description',
        'before_value',
        'after_value',
        'created_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'item_index' => 'integer',
        'before_value' => 'array',
        'after_value' => 'array',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
