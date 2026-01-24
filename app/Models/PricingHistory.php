<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_code',
        'name',
        'category',
        'supplier_id',
        'subcontractor_id',
        'import_price',
        'markup_percent',
        'markup_price',
        'changed_by',
        'changed_at',
        'source',
    ];

    protected $casts = [
        'import_price' => 'decimal:4',
        'markup_percent' => 'decimal:2',
        'markup_price' => 'decimal:4',
        'changed_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
