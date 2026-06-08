<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProposalEditLock extends Model
{
    public const TIMEOUT_MINUTES = 5;

    protected $fillable = [
        'order_proposal_id',
        'user_id',
        'lock_token',
        'started_at',
        'heartbeat_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'heartbeat_at' => 'datetime',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(OrderProposal::class, 'order_proposal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->heartbeat_at !== null
            && $this->heartbeat_at->greaterThanOrEqualTo(now()->subMinutes(self::TIMEOUT_MINUTES));
    }
}
