<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public const SIZE_TYPES = ['ropa', 'pantalon', 'calzado'];

    protected $fillable = ['name', 'description', 'slug', 'size_type', 'active'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Marcas que están dentro de esta categoría (con o sin modelos todavía).
    public function brands()
    {
        return $this->belongsToMany(Brand::class)->withTimestamps();
    }
}
