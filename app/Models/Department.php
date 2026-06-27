<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Department extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'name',
        'lead_user_id',
    ];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function categories()
    {
        return $this->hasMany(DepartmentCategory::class);
    }

    public function positions()
    {
        return $this->hasMany(DepartmentPosition::class);
    }

    public function lead()
    {
        return $this->belongsTo(User::class, 'lead_user_id');
    }
}
