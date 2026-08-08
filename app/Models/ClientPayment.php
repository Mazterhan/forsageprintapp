<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClientPayment extends Model
{
    use HasPublicId;

    protected $fillable = [
        'client_id',
        'order_id',
        'amount',
        'amount_uah',
        'calculated_amount_uah',
        'currency',
        'exchange_rate',
        'exchange_rate_type',
        'exchange_rate_source',
        'exchange_rate_fetched_at',
        'payment_type',
        'is_from_overpayment',
        'is_automatic',
        'source_payment_id',
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
        'amount_uah' => 'integer',
        'calculated_amount_uah' => 'integer',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_fetched_at' => 'datetime',
        'is_from_overpayment' => 'boolean',
        'is_automatic' => 'boolean',
        'source_payment_id' => 'integer',
        'paid_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'is_edited' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (ClientPayment $payment): void {
            if ($payment->amount_uah === null || (int) $payment->amount_uah === 0) {
                $payment->amount_uah = (int) $payment->amount;
            }
        });
    }

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

    public function automaticOverpayment(): HasOne
    {
        return $this->hasOne(self::class, 'source_payment_id');
    }

    public function hasCommentTrace(): bool
    {
        if (trim((string) $this->comment) !== '') {
            return true;
        }

        foreach ($this->histories as $history) {
            foreach ((array) $history->getAttribute('changes') as $change) {
                if (! is_array($change) || ($change['field'] ?? null) !== 'comment') {
                    continue;
                }

                foreach (['before', 'after'] as $side) {
                    $value = trim((string) ($change[$side] ?? ''));
                    if ($value !== '' && $value !== '—') {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
