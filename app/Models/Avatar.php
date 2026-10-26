<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\PublicFile;

class Avatar extends Model
{
    protected $fillable = ['path'];

    protected $appends = ['url'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function getUrlAttribute(): string
    {
        return PublicFile::url($this->path);
    }
}
