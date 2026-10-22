<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CatalogoController extends Controller
{
    private const NAME_MATCH = 'LOWER(TRIM(name)) = ?';

    public function index()
    {
        $productos = Product::with(['category', 'brand', 'variants'])
            ->withCount('colors')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->orderBy('categories.name')
            ->orderBy('brands.name')
            ->orderBy('products.name')
            ->addSelect('products.*')
            ->get()
            ->map(function ($p) {
                // El precio de venta vive por variante (cada color/talla puede costar distinto,
                // ej. una edición limitada). Si aún no hay variantes se usa el precio base del modelo.
                $precioMin = $p->variants->min('price');
                $precioMax = $p->variants->max('price');

                // Tallas únicas entre todos los colores, en el mismo orden que se usa al
                // registrar (RegistrarModeloModal/RegistrarCompraModal) — solo para tener
                // una vista previa rápida, sin tener que expandir el modelo.
                $ordenTallas = ['Único', 'XS', 'S', 'M', 'L', 'XL', 'XXL', '28', '30', '32', '34', '36', '38', '40'];
                $tallas = $p->variants->pluck('size')->unique()
                    ->sortBy(fn ($talla) => ($idx = array_search($talla, $ordenTallas)) === false ? 999 : $idx)
                    ->values();

                return [
                    'id' => $p->id,
                    'nombre' => $p->name,
                    'marca' => $p->brand->name ?? '-',
                    'categoria' => $p->category->name ?? '-',
                    'descripcion' => $p->description,
                    'stock' => $p->stock,
                    'activo' => $p->active,
                    'precioVenta' => (float) ($precioMin ?? $p->price),
                    'precioVentaMax' => ($precioMax !== null && (float) $precioMax !== (float) ($precioMin ?? $p->price)) ? (float) $precioMax : null,
                    // "Variantes" = colores del modelo (ej. blanco vs negro). Las tallas son
                    // el desglose dentro de cada color, no una variante en sí misma.
                    'variantes' => $p->colors_count,
                    'tallas' => $tallas,
                ];
            });

        return Inertia::render('Admin/Catalogo/Index', [
            'productos' => $productos,
            'categorias' => Category::orderBy('name')->pluck('name'),
            'marcas' => Brand::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'marca' => 'required|string|max:255',
            'marcaDescripcion' => 'nullable|string|max:1000',
            'categoria' => 'required|string|max:255',
            'categoriaDescripcion' => 'nullable|string|max:1000',
            'descripcion' => 'nullable|string|max:2000',
            'color' => 'required|string|max:50',
            'colorHex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'talla' => 'required|string|max:20',
            'precio' => 'required|numeric|min:0.01',
            'stock' => 'required|integer|min:0',
        ]);

        $product = DB::transaction(function () use ($validated) {
            $brand = Brand::firstOrCreate(
                ['slug' => Str::slug(trim($validated['marca']))],
                ['name' => trim($validated['marca']), 'description' => $validated['marcaDescripcion'] ?? null, 'active' => true]
            );

            $exists = Product::where('brand_id', $brand->id)
                ->whereRaw(self::NAME_MATCH, [mb_strtolower(trim($validated['nombre']))])
                ->exists();

            if ($exists) {
                return null;
            }

            $category = Category::firstOrCreate(
                ['slug' => Str::slug(trim($validated['categoria']))],
                ['name' => trim($validated['categoria']), 'description' => $validated['categoriaDescripcion'] ?? null, 'active' => true]
            );

            $sku = strtoupper(Str::slug($validated['nombre'].'-'.$validated['marca'])).'-'.Str::random(6);
            $product = Product::create([
                'name' => trim($validated['nombre']),
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'description' => $validated['descripcion'] ?? '',
                'sku' => $sku,
                'price' => $validated['precio'],
                'cost' => 0,
                'stock' => $validated['stock'],
                'image_url' => null,
                'active' => true,
            ]);

            $color = $product->colors()->create([
                'name' => trim($validated['color']),
                'hex' => $validated['colorHex'] ?? null,
                'image_url' => null,
            ]);

            $product->variants()->create([
                'product_color_id' => $color->id,
                'size' => trim($validated['talla']),
                'sku' => strtoupper($sku.'-'.Str::slug($validated['color']).'-'.Str::slug($validated['talla'])),
                'price' => $validated['precio'],
                'cost' => 0,
                'stock' => $validated['stock'],
            ]);

            return $product;
        });

        if (!$product) {
            return back()->withErrors(['nombre' => 'Ya existe un modelo con ese nombre y esa marca.']);
        }

        return redirect()->route('admin.catalogo.index')->with('success', 'Producto y variante inicial registrados.');
    }

    public function update(Request $request, Product $producto)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'marca' => 'required|string|max:255',
            'marcaDescripcion' => 'nullable|string|max:1000',
            'categoria' => 'required|string|max:255',
            'categoriaDescripcion' => 'nullable|string|max:1000',
            'descripcion' => 'nullable|string|max:2000',
        ]);

        $brand = Brand::firstOrCreate(
            ['slug' => Str::slug($validated['marca'])],
            ['name' => trim($validated['marca']), 'description' => $validated['marcaDescripcion'] ?? null, 'active' => true]
        );

        $existe = Product::where('brand_id', $brand->id)
            ->whereRaw(self::NAME_MATCH, [mb_strtolower(trim($validated['nombre']))])
            ->where('id', '!=', $producto->id)
            ->exists();

        if ($existe) {
            return back()->withErrors(['nombre' => 'Ya existe otro modelo con ese nombre y esa marca.']);
        }

        $category = Category::firstOrCreate(
            ['slug' => Str::slug($validated['categoria'])],
            ['name' => $validated['categoria'], 'description' => $validated['categoriaDescripcion'] ?? null, 'active' => true]
        );

        $producto->update([
            'name' => trim($validated['nombre']),
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'description' => $validated['descripcion'] ?? '',
        ]);

        return redirect()->route('admin.catalogo.index')->with('success', 'Modelo actualizado.');
    }

    // Agrupa por color (la "variante" real del modelo) y anida las tallas dentro
    // de cada uno, ya que la talla es solo el desglose de stock de ese color.
    public function variantes(Product $producto)
    {
        $colores = $producto->colors()
            ->with(['variants' => fn ($q) => $q->orderBy('size')])
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->name,
                'hex' => $c->hex,
                'stockTotal' => $c->variants->sum('stock'),
                'tallas' => $c->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'talla' => $v->size,
                    'stock' => $v->stock,
                    'costo' => (float) $v->cost,
                ])->values(),
            ]);

        return response()->json($colores);
    }

    public function colores(Product $producto)
    {
        $colores = $producto->colors()
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->name,
                'hex' => $c->hex,
                'imagenUrl' => $c->image_url ? Storage::disk('public')->url($c->image_url) : null,
            ]);

        return response()->json($colores);
    }

    public function storeColor(Request $request, Product $producto)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'hex' => 'nullable|string|max:7',
            'imagen' => 'nullable|image|max:4096',
        ]);

        $existe = $producto->colors()
            ->whereRaw(self::NAME_MATCH, [mb_strtolower(trim($validated['nombre']))])
            ->exists();

        if ($existe) {
            return back()->withErrors(['nombre' => 'Este modelo ya tiene un color con ese nombre.']);
        }

        $imagePath = $request->hasFile('imagen')
            ? $request->file('imagen')->store('product-colors', 'public')
            : null;

        $producto->colors()->create([
            'name' => trim($validated['nombre']),
            'hex' => $validated['hex'] ?? null,
            'image_url' => $imagePath,
        ]);

        return back()->with('success', 'Color agregado.');
    }

    public function updateColor(Request $request, ProductColor $color)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'hex' => 'nullable|string|max:7',
            'imagen' => 'nullable|image|max:4096',
        ]);

        $existe = ProductColor::where('product_id', $color->product_id)
            ->whereRaw(self::NAME_MATCH, [mb_strtolower(trim($validated['nombre']))])
            ->where('id', '!=', $color->id)
            ->exists();

        if ($existe) {
            return back()->withErrors(['nombre' => 'Este modelo ya tiene otro color con ese nombre.']);
        }

        $imagePath = $color->image_url;
        if ($request->hasFile('imagen')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('imagen')->store('product-colors', 'public');
        }

        $color->update([
            'name' => trim($validated['nombre']),
            'hex' => $validated['hex'] ?? null,
            'image_url' => $imagePath,
        ]);

        return back()->with('success', 'Color actualizado.');
    }

    public function destroyColor(ProductColor $color)
    {
        if ($color->variants()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar: hay compras registradas con este color.']);
        }

        if ($color->image_url) {
            Storage::disk('public')->delete($color->image_url);
        }

        $color->delete();

        return back()->with('success', 'Color eliminado.');
    }

    public function destroy(Product $producto)
    {
        return $this->deactivate($producto);
    }

    public function deactivate(Product $producto)
    {
        $producto->update(['active' => false]);

        return redirect()->route('admin.catalogo.index')->with('success', 'Producto dado de baja; sus ventas y compras se conservaron.');
    }

    public function restore(Product $producto)
    {
        $producto->update(['active' => true]);

        return redirect()->route('admin.catalogo.index')->with('success', 'Producto reactivado en el catálogo.');
    }
}
