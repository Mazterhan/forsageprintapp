<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'status',
        'category',
        'is_vip',
        'notes',
        'tags',
        'contact_name',
        'phones',
        'emails',
        'messengers',
        'source',
        'delivery_address',
        'delivery_notes',
        'delivery_addresses',
        'manager_id',
        'created_by',
        'updated_by',
        'last_order_at',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
