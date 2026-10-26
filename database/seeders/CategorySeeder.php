<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

// Categorías base de la tienda (la vitrina del Home, por eso van destacadas). Se pueden correr varias veces: busca por slug y solo completa
// lo que falta, así no pisa nombres, tipos de talla ni fotos que ya se cambiaron desde el admin.
class CategorySeeder extends Seeder
{
    private const CATEGORIAS = [
        ['name' => 'Zapatillas', 'slug' => 'zapatillas', 'size_type' => 'calzado', 'image' => '/image/shop/calzados.jpeg', 'featured' => true],
        ['name' => 'Polos', 'slug' => 'polos', 'size_type' => 'ropa', 'image' => '/image/shop/polos.jpeg', 'featured' => true],
        ['name' => 'Poleras', 'slug' => 'poleras', 'size_type' => 'ropa', 'image' => '/image/shop/poleras.jpg', 'featured' => true],
        ['name' => 'Pantalones', 'slug' => 'pantalones', 'size_type' => 'pantalon', 'image' => '/image/shop/pantalones.webp', 'featured' => true],
        ['name' => 'Gorras', 'slug' => 'gorras', 'size_type' => 'ropa', 'image' => '/image/shop/gorro.jpeg', 'featured' => true],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIAS as $datos) {
            $categoria = Category::firstOrCreate(['slug' => $datos['slug']], $datos);

            if (! $categoria->wasRecentlyCreated && ! $categoria->image && $datos['image']) {
                $categoria->update(['image' => $datos['image']]);
            }
        }
    }
}
