<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_number',
        'user_id',
        'deleted_by',
        'deleted_date',
        'is_autosaved',
        'autosaved_by',
        'autosaved_at',
        'autosave_token',
        'autosave_confirmed_by',
        'autosave_confirmed_at',
        'client_name',
        'total_cost',
        'corrections_count',
        'payload',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'corrections_count' => 'integer',
        'deleted_by' => 'integer',
        'deleted_date' => 'datetime',
        'is_autosaved' => 'boolean',
        'autosaved_by' => 'integer',
        'autosaved_at' => 'datetime',
        'autosave_confirmed_by' => 'integer',
        'autosave_confirmed_at' => 'datetime',
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function autosavedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autosaved_by');
    }

    public function autosaveConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autosave_confirmed_by');
    }

    public function editLock(): HasOne
    {
        return $this->hasOne(OrderProposalEditLock::class);
    }

    public function activeEditLock(): HasOne
    {
        return $this->hasOne(OrderProposalEditLock::class)
            ->where('heartbeat_at', '>=', now()->subMinutes(OrderProposalEditLock::TIMEOUT_MINUTES));
    }
}
