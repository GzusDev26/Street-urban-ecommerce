<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

// Archivos del disco "public" (fotos de colores, avatares, etc.).
class PublicFile
{
    // URL relativa (/storage/...). No usa Storage::url() porque esa arma la dirección con
    // APP_URL, y si no coincide con el dominio real (ej. localhost vs .test) la foto no carga.
    public static function url(?string $path): ?string
    {
        return $path ? '/storage/' . ltrim($path, '/') : null;
    }

    // Guarda con un nombre legible (ej. "airforce-one-blanco-k3f9a2.jpg") en vez del hash
    // aleatorio de Laravel, para que la carpeta se pueda revisar a mano. El sufijo evita
    // choques entre archivos y que el navegador muestre una foto vieja desde la caché.
    public static function storeNamed(UploadedFile $file, string $dir, string ...$parts): string
    {
        $base = Str::slug(implode(' ', $parts)) ?: 'archivo';
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $name = Str::limit($base, 80, '') . '-' . Str::lower(Str::random(6)) . '.' . $ext;

        return $file->storeAs($dir, $name, 'public');
    }
}
