<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Inertia\Inertia;

// Códigos de barras de las variantes (modelo × color × talla): ver, asignar el de fábrica y preparar
// las etiquetas para imprimir. La lectura al vender está en SaleController::buscarCodigo.
class CodigoController extends Controller
{
    private const POR_PAGINA = 50;

    // Códigos de las prendas: la búsqueda, el filtro y la paginación se hacen en la base de datos
    // (hay una fila por modelo × color × talla, así que la lista completa crece rápido).
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $filtro = in_array($request->query('filtro'), ['propio', 'fabrica'], true) ? $request->query('filtro') : 'todas';

        $paginador = $this->variantesFiltradas($buscar, $filtro)
            ->with(['product.brand', 'product.category', 'productColor'])
            ->paginate(self::POR_PAGINA, ['product_variants.*'], 'pagina');

        $prefijo = ProductVariant::PREFIJO_CODIGO . '%';

        return Inertia::render('Admin/Codigos/Index', [
            'variantes' => $paginador->getCollection()->map(fn ($v) => $this->mapVariante($v))->values(),
            'paginacion' => ['pagina' => $paginador->currentPage(), 'total' => $paginador->total(), 'porPagina' => self::POR_PAGINA],
            'filtros' => ['buscar' => $buscar, 'filtro' => $filtro],
            'conteos' => [
                'todas' => ProductVariant::count(),
                'propio' => ProductVariant::where('barcode', 'like', $prefijo)->count(),
                'fabrica' => ProductVariant::where('barcode', 'not like', $prefijo)->count(),
            ],
        ]);
    }

    // Todas las prendas del filtro, en formato compacto, para «una etiqueta por unidad en stock» sobre todo el
    // filtro (y no solo la página que se ve).
    public function etiquetas(Request $request)
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $filtro = in_array($request->query('filtro'), ['propio', 'fabrica'], true) ? $request->query('filtro') : 'todas';

        return response()->json(
            $this->variantesFiltradas($buscar, $filtro)
                ->with(['product.brand', 'product.category', 'productColor'])
                ->get(['product_variants.*'])
                ->map(fn ($v) => $this->mapVariante($v))
                ->values()
        );
    }

    private function variantesFiltradas(string $buscar, string $filtro)
    {
        $prefijo = ProductVariant::PREFIJO_CODIGO . '%';

        $query = ProductVariant::query()
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('product_colors', 'product_colors.id', '=', 'product_variants.product_color_id')
            ->orderBy('products.name')
            ->orderBy('products.id')
            ->orderBy('product_colors.name')
            ->orderBy('product_variants.size')
            ->orderBy('product_variants.id');

        match ($filtro) {
            'propio' => $query->where('product_variants.barcode', 'like', $prefijo),
            'fabrica' => $query->where('product_variants.barcode', 'not like', $prefijo),
            default => null,
        };

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('products.name', 'like', "%{$buscar}%")
                    ->orWhere('product_colors.name', 'like', "%{$buscar}%")
                    ->orWhere('product_variants.size', 'like', "%{$buscar}%")
                    ->orWhere('product_variants.barcode', 'like', "%{$buscar}%")
                    ->orWhereHas('product.brand', fn ($b) => $b->where('name', 'like', "%{$buscar}%"))
                    ->orWhereHas('product.category', fn ($c) => $c->where('name', 'like', "%{$buscar}%"));
            });
        }

        return $query;
    }

    private function mapVariante(ProductVariant $v): array
    {
        return [
            'id' => $v->id,
            'codigo' => $v->barcode,
            'propio' => str_starts_with((string) $v->barcode, ProductVariant::PREFIJO_CODIGO),
            'producto' => $v->product->name ?? '-',
            'marca' => $v->product->brand->name ?? '-',
            'categoria' => $v->product->category->name ?? '-',
            'color' => $v->productColor->name ?? 'Sin color',
            'colorHex' => $v->productColor->hex ?? null,
            'talla' => $v->size,
            'stock' => (int) $v->stock,
            'precio' => (float) ($v->price ?? $v->product->price ?? 0),
        ];
    }

    // Asigna el código de fábrica escaneado/escrito. Vacío = vuelve al código propio de la variante.
    public function update(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate(['codigo' => 'nullable|string|max:64']);

        $codigo = ProductVariant::normalizarCodigo($validated['codigo'] ?? '');

        if ($codigo === '') {
            $codigo = ProductVariant::codigoPropio($variant->id);
        }

        $dueño = ProductVariant::with(['product', 'productColor'])
            ->where('barcode', $codigo)
            ->where('id', '!=', $variant->id)
            ->first();

        if ($dueño) {
            $nombre = ($dueño->product->name ?? 'otra prenda') . ' · ' . ($dueño->productColor->name ?? 'Sin color') . ' · ' . $dueño->size;

            return back()->withErrors(['codigo' => "El código {$codigo} ya pertenece a {$nombre}."]);
        }

        $variant->update(['barcode' => $codigo]);

        return back()->with('success', 'Código actualizado.');
    }
}
