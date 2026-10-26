<?php

namespace App\Models;

use App\Models\Concerns\FormatsName;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use FormatsName;

    public const SIZE_TYPES = ['ropa', 'pantalon', 'calzado', 'unico'];

    protected $fillable = ['name', 'description', 'slug', 'size_type', 'image', 'featured', 'active'];

    protected $casts = [
        'featured' => 'boolean',
    ];

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
