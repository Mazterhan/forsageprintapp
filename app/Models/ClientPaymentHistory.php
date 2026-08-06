<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPaymentHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'client_payment_id',
        'user_id',
        'changes',
        'created_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ClientPayment::class, 'client_payment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
