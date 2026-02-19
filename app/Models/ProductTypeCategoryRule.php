<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductTypeCategoryRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_category_id',
        'product_type_id',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }
}

