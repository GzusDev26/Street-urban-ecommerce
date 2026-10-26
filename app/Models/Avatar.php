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
        return $this->esDelSitio() ? $this->path : PublicFile::url($this->path);
    }

    // Los avatares base viven en public/image/avatares (van en Git, ruta que empieza con "/");
    // los que sube un admin están en storage/app/public/avatars.
    public function esDelSitio(): bool
    {
        return str_starts_with($this->path, '/image/');
    }
}
