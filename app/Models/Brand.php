<?php

namespace App\Models;

use App\Models\Concerns\FormatsName;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use FormatsName;

    protected $fillable = ['name', 'description', 'slug', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }
}
