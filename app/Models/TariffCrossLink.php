<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Supplier;
use App\Models\Tariff;

class TariffCrossLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_tariff_id',
        'child_internal_code',
        'child_supplier_id',
        'created_by',
    ];

    public function parent()
    {
        return $this->belongsTo(Tariff::class, 'parent_tariff_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'child_supplier_id');
    }
}
