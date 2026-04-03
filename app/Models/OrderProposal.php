<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_number',
        'user_id',
        'client_name',
        'total_cost',
        'corrections_count',
        'payload',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'corrections_count' => 'integer',
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
