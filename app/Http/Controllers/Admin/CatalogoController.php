<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Support\NameMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CatalogoController extends Controller
{
    private const NAME_MATCH = 'LOWER(TRIM(name)) = ?';

    // El catálogo vive dentro de Compras > Inventario (árbol categoría → marca → modelo).
    public function index()
    {
        return redirect()->route('admin.purchases.index');
    }

    // Crea un modelo dentro de una categoría y marca ya elegidas en el árbol (sin colores, tallas
    // ni stock: los colores se agregan después y el stock entra por Compras).
    public function store(Request $request)
    {
        $validated = $request->validate([
            'categoria_id' => 'required|exists:categories,id',
            'marca_id' => 'required|exists:brands,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
        ]);

        $category = Category::findOrFail($validated['categoria_id']);
        $brand = Brand::findOrFail($validated['marca_id']);
        $nombre = trim($validated['nombre']);

        $existe = Product::where('brand_id', $brand->id)
            ->whereRaw(self::NAME_MATCH, [mb_strtolower($nombre)])
            ->exists();

        if ($existe) {
            $mensaje = "{$brand->name} ya tiene un modelo llamado «{$nombre}».";

            return $request->expectsJson()
                ? response()->json(['message' => $mensaje], 422)
                : back()->withErrors(['nombre' => $mensaje]);
        }

        $producto = Product::create([
            'name' => $nombre,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'description' => $validated['descripcion'] ?? '',
            'sku' => strtoupper(Str::slug($nombre.'-'.$brand->name)).'-'.Str::random(6),
            'price' => 0,
            'cost' => 0,
            'stock' => 0,
            'image_url' => null,
            'active' => true,
        ]);

        $category->brands()->syncWithoutDetaching([$brand->id]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $producto->id,
                'nombre' => $producto->name,
                'marca' => $brand->name,
                'categoria' => $category->name,
                'stock' => 0,
            ], 201);
        }

        return back()->with('success', "Modelo «{$nombre}» creado en {$category->name} › {$brand->name}.");
    }

    // Agrega tallas al modelo: crea la combinación talla × color de cada color existente, con 0 de
    // stock. Sirve para dejar la estructura lista; el stock y los precios entran con la compra.
    public function storeTallas(Request $request, Product $producto)
    {
        $validated = $request->validate([
            'tallas' => 'required|array|min:1|max:30',
            'tallas.*' => 'required|string|max:20',
        ]);

        $colores = $producto->colors;

        if ($colores->isEmpty()) {
            return back()->withErrors(['tallas' => 'Agrega al menos un color antes de agregar tallas.']);
        }

        $creadas = 0;

        DB::transaction(function () use ($validated, $producto, $colores, &$creadas) {
            foreach ($colores as $color) {
                foreach (array_unique(array_map('trim', $validated['tallas'])) as $talla) {
                    $variante = ProductVariant::firstOrCreate(
                        ['product_id' => $producto->id, 'size' => $talla, 'product_color_id' => $color->id],
                        ['sku' => strtoupper($producto->sku.'-'.$talla.'-'.Str::slug($color->name)).'-'.Str::random(3), 'stock' => 0],
                    );

                    if ($variante->wasRecentlyCreated) {
                        $creadas++;
                    }
                }
            }
        });

        return back()->with('success', $creadas === 0
            ? 'Esas tallas ya estaban registradas.'
            : "{$creadas} combinaci".($creadas === 1 ? 'ón' : 'ones').' de talla y color creadas.');
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

        $marcaExistente = Brand::whereRaw(self::NAME_MATCH, [mb_strtolower(trim($validated['marca']))])->first();

        if (!$marcaExistente && ($parecida = NameMatcher::similar(Brand::class, $validated['marca']))) {
            return back()->withErrors(['marca' => "La marca «{$validated['marca']}» se parece a «{$parecida->name}». Elige esa de la lista."]);
        }

        $brand = $marcaExistente ?? Brand::create([
            'slug' => Str::slug($validated['marca']),
            'name' => trim($validated['marca']),
            'description' => $validated['marcaDescripcion'] ?? null,
            'active' => true,
        ]);

        $existe = Product::where('brand_id', $brand->id)
            ->whereRaw(self::NAME_MATCH, [mb_strtolower(trim($validated['nombre']))])
            ->where('id', '!=', $producto->id)
            ->exists();

        if ($existe) {
            return back()->withErrors(['nombre' => 'Ya existe otro modelo con ese nombre y esa marca.']);
        }

        // Las categorías no se crean desde aquí: se elige una existente.
        $category = Category::whereRaw(self::NAME_MATCH, [mb_strtolower(trim($validated['categoria']))])->first();

        if (!$category) {
            return back()->withErrors(['categoria' => 'Esa categoría no existe. Elige una de la lista.']);
        }

        $producto->update([
            'name' => trim($validated['nombre']),
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'description' => $validated['descripcion'] ?? '',
        ]);

        $category->brands()->syncWithoutDetaching([$brand->id]);

        return back()->with('success', 'Modelo actualizado.');
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

    // Alta rápida de varios colores a la vez (solo nombre y color de referencia; las fotos se
    // suben después, editando cada color). Los que el modelo ya tiene se omiten, sin error.
    public function storeColores(Request $request, Product $producto)
    {
        $validated = $request->validate([
            'colores' => 'required|array|min:1|max:30',
            'colores.*.nombre' => 'required|string|max:50',
            'colores.*.hex' => 'nullable|string|max:7',
        ]);

        $creados = 0;
        $omitidos = 0;

        DB::transaction(function () use ($validated, $producto, &$creados, &$omitidos) {
            foreach ($validated['colores'] as $color) {
                $nombre = trim($color['nombre']);

                $existe = $producto->colors()
                    ->whereRaw(self::NAME_MATCH, [mb_strtolower($nombre)])
                    ->exists();

                if ($existe) {
                    $omitidos++;
                    continue;
                }

                $producto->colors()->create([
                    'name' => $nombre,
                    'hex' => $color['hex'] ?? null,
                    'image_url' => null,
                ]);
                $creados++;
            }
        });

        $mensaje = $creados === 1 ? '1 color agregado.' : "{$creados} colores agregados.";
        if ($omitidos > 0) {
            $mensaje .= ' ' . ($omitidos === 1 ? '1 ya existía y se omitió.' : "{$omitidos} ya existían y se omitieron.");
        }

        return back()->with('success', $mensaje);
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

    // Sube o reemplaza solo la foto del color, sin pedir nombre ni color de referencia:
    // es lo que usa la foto clicable del inventario y del modal de colores.
    public function updateColorPhoto(Request $request, ProductColor $color)
    {
        $request->validate([
            'imagen' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'imagen.required' => 'Elige una foto.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La foto debe ser JPG, PNG o WEBP.',
            'imagen.max' => 'La foto no puede pesar más de 4 MB.',
        ]);

        $anterior = $color->image_url;
        $color->update(['image_url' => $request->file('imagen')->store('product-colors', 'public')]);

        if ($anterior) {
            Storage::disk('public')->delete($anterior);
        }

        return back()->with('success', "Foto de {$color->name} actualizada.");
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

        return back()->with('success', 'Producto dado de baja; sus ventas y compras se conservaron.');
    }

    public function restore(Product $producto)
    {
        $producto->update(['active' => true]);

        return back()->with('success', 'Producto reactivado en el catálogo.');
    }
}
