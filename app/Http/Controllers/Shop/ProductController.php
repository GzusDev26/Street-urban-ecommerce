<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\SaleItem;
use Inertia\Inertia;
use App\Support\PublicFile;

class ProductController extends Controller
{
    private function mapProduct(Product $p, array $vendidosPorProducto = []): array
    {
        $colores = $p->colors
            ->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->name,
                'hex' => $c->hex,
                'imagen' => PublicFile::url($c->image_url),
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
        $imageFallback = PublicFile::url($p->image_url);

        return [
            'id' => $p->id,
            'name' => $p->name,
            'description' => $p->description,
            'price' => (float) $precioBase,
            'category' => $p->category->name ?? 'Sin categoría',
            'brand' => $p->brand->name ?? 'Sin marca',
            'stock' => $p->stock > 0,
            'sold' => $vendidosPorProducto[$p->id] ?? 0,
            // "Nuevo" según la fecha real de alta (antes era una lista fija de ids).
            'isNew' => $p->created_at?->gt(now()->subDays(30)) ?? false,
            'image' => $colorConFoto
                ? PublicFile::url($colorConFoto->image_url)
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

    // Vitrina de categorías del Home: solo las destacadas (featured), para que la portada
    // mantenga una composición curada aunque se creen más categorías desde el admin.
    // Las que aún no tienen productos salen como "Próximamente" (sin enlace).
    // Imagen: la propia de la categoría, si no la foto de uno de sus productos.
    private function getCategories($products)
    {
        $porCategoria = $products->groupBy('category');

        return Category::where('active', true)
            ->where('featured', true)
            ->orderBy('id')
            ->get()
            ->map(function (Category $categoria) use ($porCategoria) {
                $productos = $porCategoria->get($categoria->name, collect());
                $conFoto = $productos->firstWhere('image', '!=', null);

                return [
                    'name' => $categoria->name,
                    'image' => $categoria->image ?? $conFoto['image'] ?? null,
                    'count' => $productos->count(),
                ];
            })
            // Primero las que ya tienen productos; el resto mantiene su orden.
            ->sortBy(fn ($c) => $c['count'] > 0 ? 0 : 1, SORT_REGULAR, false)
            ->values();
    }

    public function index()
    {
        $products = $this->getProducts();

        return Inertia::render('Shop/Home', [
            'products' => $products,
            'categories' => $this->getCategories($products),
            // Categorías activas fuera de la vitrina: si hay alguna, el Home ofrece "Ver todas".
            'moreCategories' => Category::where('active', true)->where('featured', false)->count(),
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
