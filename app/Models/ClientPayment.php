<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientPayment extends Model
{
    use HasPublicId;

    protected $fillable = [
        'client_id',
        'order_id',
        'amount',
        'currency',
        'payment_type',
        'is_from_overpayment',
        'paid_at',
        'comment',
        'created_by',
        'updated_by',
        'is_edited',
    ];

    protected $casts = [
        'client_id' => 'integer',
        'order_id' => 'integer',
        'amount' => 'integer',
        'is_from_overpayment' => 'boolean',
        'paid_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'is_edited' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ClientPaymentHistory::class)->latest('created_at');
    }
}
