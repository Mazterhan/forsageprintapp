<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DepartmentCategory;

class DepartmentPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'department_category_id',
        'name',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function category()
    {
        return $this->belongsTo(DepartmentCategory::class, 'department_category_id');
    }
}
