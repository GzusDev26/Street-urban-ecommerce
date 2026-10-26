<?php

namespace App\Models;

use App\Models\Concerns\FormatsName;
use Illuminate\Database\Eloquent\Model;

class ProductColor extends Model
{
    use FormatsName;

    protected $fillable = ['product_id', 'name', 'hex', 'image_url'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
