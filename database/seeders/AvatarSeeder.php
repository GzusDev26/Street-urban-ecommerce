<?php

namespace Database\Seeders;

use App\Models\Avatar;
use Illuminate\Database\Seeder;

// Avatares base (public/image/avatares, van en Git). Se puede correr varias veces: busca por ruta y
// solo agrega los que faltan, sin tocar los que subió un admin.
class AvatarSeeder extends Seeder
{
    private const ARCHIVOS = [
        'nike-gorro',
        'adidas-trenzas',
        'chaqueta-roja',
        'puma-audifonos',
        'puma-bucket',
        'vans-bandana',
    ];

    public function run(): void
    {
        foreach (self::ARCHIVOS as $nombre) {
            Avatar::firstOrCreate(['path' => "/image/avatares/{$nombre}.webp"]);
        }
    }
}
