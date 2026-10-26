<?php

namespace App\Models;

use App\Models\Concerns\FormatsName;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use FormatsName;

    protected $fillable = ['name', 'brand_id', 'description', 'sku', 'price', 'cost', 'stock', 'category_id', 'image_url', 'active'];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function colors()
    {
        return $this->hasMany(ProductColor::class);
    }
}
