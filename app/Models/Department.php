<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'lead_user_id',
    ];

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
