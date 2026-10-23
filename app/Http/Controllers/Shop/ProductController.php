<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductController extends Controller
{
    private function mapProduct(Product $p, array $vendidosPorProducto = []): array
    {
        $colores = $p->colors
            ->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->name,
                'hex' => $c->hex,
                'imagen' => $c->image_url ? Storage::disk('public')->url($c->image_url) : null,
                'tallas' => $c->variants
                    ->map(fn ($v) => [
                        'variant_id' => $v->id,
                        'talla' => $v->size,
                        'stock' => $v->stock,
                        'precio' => (float) ($v->price ?? $p->price),
                    ])
                    ->values(),
            ])
            ->filter(fn ($c) => $c['tallas']->count() > 0)
            ->map(fn ($c) => $c + ['precio' => $c['tallas']->min('precio')])
            ->values();

        $colorConFoto = $p->colors->first(fn ($c) => $c->image_url);
        $precioBase = $colores->isNotEmpty() ? $colores->min('precio') : (float) $p->price;
        $imageFallback = $p->image_url ? Storage::disk('public')->url($p->image_url) : null;

        return [
            'id' => $p->id,
            'name' => $p->name,
            'description' => $p->description,
            'price' => (float) $precioBase,
            'category' => $p->category->name ?? 'Sin categoría',
            'brand' => $p->brand->name ?? 'Sin marca',
            'stock' => $p->stock > 0,
            'rating' => 4.5,
            'sold' => $vendidosPorProducto[$p->id] ?? 0,
            'image' => $colorConFoto
                ? Storage::disk('public')->url($colorConFoto->image_url)
                : $imageFallback,
            'colores' => $colores,
        ];
    }

    // Unidades vendidas por producto, según las ventas registradas en el módulo de Ventas
    // del admin, excluyendo las que fueron canceladas (no fueron una venta real).
    private function getVendidosPorProducto()
    {
        return SaleItem::join('product_variants', 'product_variants.id', '=', 'sale_items.product_variant_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.cancelled_at')
            ->selectRaw('product_variants.product_id as product_id, sum(sale_items.quantity) as total')
            ->groupBy('product_variants.product_id')
            ->pluck('total', 'product_id');
    }

    private function getProducts()
    {
        $vendidos = $this->getVendidosPorProducto();

        // Un modelo recién creado en el inventario todavía no tiene precio (eso entra con la primera
        // compra): no se muestra en la tienda hasta tener al menos una variante con precio.
        return Product::with(['brand', 'category', 'colors.variants'])
            ->where('active', true)
            ->whereHas('variants', fn ($q) => $q->whereNotNull('price'))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($p) => $this->mapProduct($p, $vendidos->toArray()));
    }

    // Deriva las categorías a mostrar en el Home directo de los productos activos,
    // usando la foto de uno de sus productos como imagen. Así, al agregar una
    // categoría nueva (ej. "Medias") aparece sola, sin tocar código.
    private function getCategories($products)
    {
        return $products
            ->groupBy('category')
            ->map(function ($group, $name) {
                $conFoto = $group->firstWhere('image', '!=', null) ?? $group->first();

                return [
                    'name' => $name,
                    'image' => $conFoto['image'] ?? null,
                    'count' => $group->count(),
                ];
            })
            ->values();
    }

    public function index()
    {
        $products = $this->getProducts();

        return Inertia::render('Shop/Home', [
            'products' => $products,
            'categories' => $this->getCategories($products),
        ]);
    }

    public function shop()
    {
        return Inertia::render('Shop/Tienda', [
            'products' => $this->getProducts()
        ]);
    }

    public function show(Product $producto)
    {
        abort_unless($producto->active, 404);

        $producto->load(['brand', 'category', 'colors.variants']);

        return Inertia::render('Shop/ProductDetail', [
            'producto' => $this->mapProduct($producto, $this->getVendidosPorProducto()->toArray()),
        ]);
    }
}
