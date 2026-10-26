<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\NameMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryBrandController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Taxonomias', [
            'categorias' => Category::withCount('products')->orderBy('name')->get()
                ->map(fn ($c) => $this->mapItem($c)),
            'marcas' => Brand::withCount('products')->orderBy('name')->get()
                ->map(fn ($m) => $this->mapItem($m)),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:1000',
            'size_type' => ['nullable', Rule::in(Category::SIZE_TYPES)],
        ]);

        if ($similar = NameMatcher::similar(Category::class, $validated['name'])) {
            return back()->withErrors(['error' => $this->mensajeParecido('categoría', $validated['name'], $similar->name)]);
        }

        Category::create([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'slug' => $this->uniqueSlug(Category::class, $validated['name']),
            'size_type' => $validated['size_type'] ?? 'ropa',
            'active' => true,
        ]);

        return back()->with('success', 'Categoría creada correctamente.');
    }

    // Alta rápida desde el formulario de compra (responde JSON, sin salir de la pantalla).
    public function quickStoreCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'size_type' => ['required', Rule::in(Category::SIZE_TYPES)],
        ], [
            'name.unique' => 'Esa categoría ya existe: elígela de la lista.',
        ]);

        if ($similar = NameMatcher::similar(Category::class, $validated['name'])) {
            return response()->json(['message' => $this->mensajeParecido('categoría', $validated['name'], $similar->name)], 422);
        }

        $category = Category::create([
            'name' => trim($validated['name']),
            'slug' => $this->uniqueSlug(Category::class, $validated['name']),
            'size_type' => $validated['size_type'],
            'active' => true,
        ]);

        return response()->json(['id' => $category->id, 'name' => $category->name, 'sizeType' => $category->size_type], 201);
    }

    public function updateCategory(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string|max:1000',
            'size_type' => ['nullable', Rule::in(Category::SIZE_TYPES)],
        ]);

        if ($similar = NameMatcher::similar(Category::class, $validated['name'], $category->id)) {
            return back()->withErrors(['error' => $this->mensajeParecido('categoría', $validated['name'], $similar->name)]);
        }

        $category->update([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'size_type' => $validated['size_type'] ?? $category->size_type,
            'slug' => $this->uniqueSlug(Category::class, $validated['name'], $category->id),
        ]);

        return back()->with('success', 'Categoría actualizada correctamente.');
    }

    // Vitrina del Home: decide si la categoría aparece en la portada de la tienda.
    public function updateCategoryFeatured(Request $request, Category $category)
    {
        $validated = $request->validate(['featured' => 'required|boolean']);

        $category->update(['featured' => $validated['featured']]);

        return back()->with('success', $validated['featured']
            ? "«{$category->name}» ahora se muestra en el Home."
            : "«{$category->name}» ya no se muestra en el Home.");
    }

    // Foto de portada de la categoría en el Home. Se guarda como ruta relativa (/storage/...)
    // para que funcione en cualquier dominio; al reemplazarla se borra la subida anterior,
    // pero nunca las imágenes base de /image/shop.
    public function updateCategoryImage(Request $request, Category $category)
    {
        $request->validate([
            'imagen' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'Usa una imagen JPG, PNG o WEBP.',
            'imagen.max' => 'La imagen no puede pesar más de 4 MB.',
        ]);

        $anterior = $category->image;
        $ruta = $request->file('imagen')->store('categorias', 'public');
        $category->update(['image' => '/storage/' . $ruta]);

        if ($anterior && str_starts_with($anterior, '/storage/categorias/')) {
            Storage::disk('public')->delete(substr($anterior, strlen('/storage/')));
        }

        return back()->with('success', "Foto de «{$category->name}» actualizada.");
    }

    public function destroyCategory(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['error' => "No puedes eliminar \"{$category->name}\": tiene productos asociados."]);
        }

        if ($category->image && str_starts_with($category->image, '/storage/categorias/')) {
            Storage::disk('public')->delete(substr($category->image, strlen('/storage/')));
        }

        $category->delete();

        return back()->with('success', 'Categoría eliminada correctamente.');
    }

    public function storeBrand(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($similar = NameMatcher::similar(Brand::class, $validated['name'])) {
            return back()->withErrors(['error' => $this->mensajeParecido('marca', $validated['name'], $similar->name)]);
        }

        Brand::create([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'slug' => $this->uniqueSlug(Brand::class, $validated['name']),
            'active' => true,
        ]);

        return back()->with('success', 'Marca creada correctamente.');
    }

    // Agrega una marca DENTRO de una categoría: usa la que ya existe con ese nombre o la crea.
    public function attachBrand(Request $request, Category $category)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        $nombre = trim($validated['name']);

        // Desde el formulario de compra se llama por JSON (sin salir de la pantalla); desde el árbol, con redirección.
        $fallo = fn (string $mensaje) => $request->expectsJson()
            ? response()->json(['message' => $mensaje], 422)
            : back()->withErrors(['error' => $mensaje]);

        $brand = Brand::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($nombre)])->first();

        if (!$brand) {
            if ($similar = NameMatcher::similar(Brand::class, $nombre)) {
                return $fallo($this->mensajeParecido('marca', $nombre, $similar->name));
            }

            $brand = Brand::create([
                'name' => $nombre,
                'slug' => $this->uniqueSlug(Brand::class, $nombre),
                'active' => true,
            ]);
        }

        if ($category->brands()->where('brands.id', $brand->id)->exists()) {
            return $fallo("«{$brand->name}» ya está en {$category->name}.");
        }

        $category->brands()->attach($brand->id);

        if ($request->expectsJson()) {
            return response()->json(['id' => $brand->id, 'name' => $brand->name], 201);
        }

        return back()->with('success', "Marca «{$brand->name}» agregada a {$category->name}.");
    }

    // Saca una marca de la categoría solo si no tiene modelos allí (la marca en sí no se borra).
    public function detachBrand(Category $category, Brand $brand)
    {
        if (Product::where('category_id', $category->id)->where('brand_id', $brand->id)->exists()) {
            return back()->withErrors(['error' => "No puedes quitar «{$brand->name}» de {$category->name}: tiene modelos."]);
        }

        $category->brands()->detach($brand->id);

        return back()->with('success', "Marca «{$brand->name}» quitada de {$category->name}.");
    }

    public function updateBrand(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,' . $brand->id,
            'description' => 'nullable|string|max:1000',
        ]);

        if ($similar = NameMatcher::similar(Brand::class, $validated['name'], $brand->id)) {
            return back()->withErrors(['error' => $this->mensajeParecido('marca', $validated['name'], $similar->name)]);
        }

        $brand->update([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'slug' => $this->uniqueSlug(Brand::class, $validated['name'], $brand->id),
        ]);

        return back()->with('success', 'Marca actualizada correctamente.');
    }

    public function destroyBrand(Brand $brand)
    {
        if ($brand->products()->exists()) {
            return back()->withErrors(['error' => "No puedes eliminar \"{$brand->name}\": tiene productos asociados."]);
        }

        $brand->delete();

        return back()->with('success', 'Marca eliminada correctamente.');
    }

    private function mapItem($item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'productos' => $item->products_count,
            'size_type' => $item->size_type ?? null,
            // Solo categorías: datos de la vitrina del Home.
            'image' => $item->image ?? null,
            'featured' => (bool) ($item->featured ?? false),
        ];
    }

    private function mensajeParecido(string $tipo, string $nuevo, string $existente): string
    {
        return "Ya existe la {$tipo} «{$existente}», muy parecida a «{$nuevo}». Usa esa o elige un nombre distinto.";
    }

    private function uniqueSlug(string $model, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while ($model::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
