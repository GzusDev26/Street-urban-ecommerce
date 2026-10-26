<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['name', 'document_type', 'document_number', 'phone', 'email', 'address'];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
