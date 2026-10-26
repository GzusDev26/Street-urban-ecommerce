<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Services\ApiPeruService;
use App\Support\ExcelReport;
use App\Support\NameMatcher;
use App\Support\ReportePeriodo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Support\PublicFile;

class PurchaseController extends Controller
{
    private const NAME_MATCH = 'LOWER(TRIM(name)) = ?';

    public function index(Request $request)
    {
        // Valor real de inventario: suma de lo que queda de cada lote a su costo real (FIFO),
        // no cantidad total x último costo, ya que un mismo producto puede tener lotes a precios distintos.
        $valorInventario = PurchaseItem::where('quantity_remaining', '>', 0)
            ->get()
            ->sum(fn ($item) => $item->quantity_remaining * $item->unit_cost);

        return Inertia::render('Admin/Compras/Index', [
            // La vista «Lista» (variantes con sus lotes) se pide aparte, ya filtrada y paginada, solo cuando se abre.
            'lista' => Inertia::optional(fn () => $this->listaVariantes($request)),
            'modelos' => $this->modelosParaArbol(),
            'categorias' => Category::orderBy('name')->pluck('name'),
            'categoriasInfo' => Category::with('brands')->orderBy('name')->get()->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'sizeType' => $c->size_type,
                // Marcas dentro de la categoría, tengan modelos o no todavía.
                'marcas' => $c->brands->sortBy('name')->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values(),
            ]),
            'marcas' => Brand::orderBy('name')->pluck('name'),
            // Marcas de todo el negocio (una sola "Nike" sirve para polos, zapatillas...).
            'marcasInfo' => Brand::orderBy('name')->get(['id', 'name']),
            'stats' => [
                'totalProductos' => Product::count(),
                'totalVariantes' => ProductVariant::count(),
                'sinStock' => ProductVariant::where('stock', 0)->count(),
                'valorInventario' => $valorInventario,
            ],
        ]);
    }

    // Vista «Lista» del inventario: variantes filtradas y paginadas en la base de datos.
    private function listaVariantes(Request $request): array
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $categoria = $request->query('categoria');
        $stock = $request->query('stock');
        $umbral = BusinessSetting::current()->low_stock_threshold;

        $query = ProductVariant::query()
            ->with([
                'product.category',
                'product.brand',
                'productColor',
                'purchaseItems' => fn ($q) => $q->with('purchase')->latest('created_at'),
            ])
            ->whereHas('product', function ($p) use ($buscar, $categoria) {
                if ($buscar !== '') {
                    $p->where(fn ($q) => $q->where('name', 'like', "%{$buscar}%")
                        ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$buscar}%")));
                }
                if ($categoria) {
                    $p->whereHas('category', fn ($c) => $c->where('name', $categoria));
                }
            })
            ->when($stock === 'sin', fn ($q) => $q->where('stock', 0))
            ->when($stock === 'bajo', fn ($q) => $q->where('stock', '>', 0)->where('stock', '<', $umbral))
            ->when($stock === 'con', fn ($q) => $q->where('stock', '>', 0))
            // Por modelo (A-Z) y color, para que las variantes de un mismo modelo queden juntas.
            ->orderBy(Product::select('name')->whereColumn('products.id', 'product_variants.product_id'))
            ->orderBy('product_id')
            ->orderBy('product_color_id')
            ->orderBy('size')
            ->orderBy('id');

        $paginador = $query->paginate(self::POR_PAGINA, ['*'], 'pagina', max(1, (int) $request->query('pagina', 1)));

        return [
            'data' => $paginador->getCollection()->map(function ($variant) {
                // Último lote registrado de esta variante: se usa para sugerir el mismo proveedor al
                // «Reabastecer» (una variante puede haberse comprado a más de un proveedor; es solo una sugerencia).
                $ultimoLote = $variant->purchaseItems->first();

                return [
                    'id' => $variant->id,
                    'productId' => $variant->product_id,
                    'producto' => $variant->product->name,
                    'marca' => $variant->product->brand->name ?? '-',
                    'categoria' => $variant->product->category->name ?? '-',
                    'talla' => $variant->size,
                    'color' => $variant->productColor->name ?? '-',
                    'colorHex' => $variant->productColor->hex ?? null,
                    'stock' => $variant->stock,
                    'precioCompra' => (float) $variant->cost,
                    'precioVenta' => (float) $variant->product->price,
                    'ultimoProveedor' => $ultimoLote?->purchase?->supplier ?: null,
                ];
            })->values(),
            'paginacion' => ['pagina' => $paginador->currentPage(), 'total' => $paginador->total(), 'porPagina' => self::POR_PAGINA],
        ];
    }

    // Todo el inventario agrupable por categoría → marca → modelo → color × talla. El árbol se arma
    // en el navegador a partir de esta lista plana: la marca no depende de la categoría (Nike vende
    // polos y shorts), solo aparece bajo cada categoría donde tiene modelos.
    private function modelosParaArbol(): array
    {
        return Product::with(['category', 'brand', 'colors.variants' => fn ($q) => $q->orderBy('size')])
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                $variantes = $p->colors->flatMap->variants;

                return [
                    'id' => $p->id,
                    'nombre' => $p->name,
                    'descripcion' => $p->description,
                    'activo' => (bool) $p->active,
                    'categoriaId' => $p->category_id,
                    'categoria' => $p->category->name ?? '-',
                    'marcaId' => $p->brand_id,
                    'marca' => $p->brand->name ?? 'Sin marca',
                    'stock' => (int) $variantes->sum('stock'),
                    'precioMin' => $variantes->count() ? (float) $variantes->min('price') : null,
                    'precioMax' => $variantes->count() ? (float) $variantes->max('price') : null,
                    'colores' => $p->colors->map(fn ($c) => [
                        'id' => $c->id,
                        'nombre' => $c->name,
                        'hex' => $c->hex,
                        'imagenUrl' => PublicFile::url($c->image_url),
                        'variantes' => $c->variants->map(fn ($v) => [
                            'id' => $v->id,
                            'talla' => $v->size,
                            'stock' => (int) $v->stock,
                            'costo' => (float) $v->cost,
                            'precio' => (float) $v->price,
                        ])->values(),
                    ])->values(),
                ];
            })->values()->all();
    }

    private function mapCompra(Purchase $p): array
    {
        return [
            'id' => $p->id,
            'numero' => 'C-' . str_pad($p->id, 5, '0', STR_PAD_LEFT),
            'fecha' => $p->purchase_date->format('d/m/Y'),
            'proveedor' => $p->supplier,
            'factura' => $p->invoice_number,
            'notas' => $p->notes,
            'total' => (float) $p->total,
            'registradoPor' => $p->user->name ?? null,
            'totalPrendas' => $p->items->sum('quantity'),
            'cancelada' => $p->cancelled_at !== null,
            'canceladaPor' => $p->cancelledBy->name ?? null,
            'items' => $p->items->map(fn ($item) => [
                'producto' => $item->variant->product->name,
                'marca' => $item->variant->product->brand->name ?? '-',
                'talla' => $item->variant->size,
                'color' => $item->variant->productColor->name ?? '-',
                'colorHex' => $item->variant->productColor->hex ?? null,
                'cantidad' => $item->quantity,
                'costoUnitario' => (float) $item->unit_cost,
                'subtotal' => (float) $item->subtotal,
            ]),
        ];
    }

    private const POR_PAGINA = 15;

    // Historial de compras: la búsqueda y la paginación se hacen en la base de datos, para cargar solo
    // las 15 compras que muestra la pantalla (y no todo el historial).
    public function historial(Request $request)
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $filtrada = fn () => $this->comprasFiltradas($buscar);

        // Desde el historial de un proveedor (?compra=123) se abre la página donde está esa compra.
        $compraId = (int) $request->query('compra');
        $pagina = max(1, (int) $request->query('pagina', 1));
        if ($compraId && ! $request->has('pagina')) {
            $objetivo = Purchase::find($compraId);
            if ($objetivo && $filtrada()->whereKey($compraId)->exists()) {
                $antes = $filtrada()->where(fn ($q) => $q
                    ->where('purchase_date', '>', $objetivo->purchase_date->toDateString())
                    ->orWhere(fn ($q2) => $q2->where('purchase_date', $objetivo->purchase_date->toDateString())->where('id', '>', $objetivo->id)))->count();
                $pagina = intdiv($antes, self::POR_PAGINA) + 1;
            }
        }

        $paginador = $filtrada()
            ->with(['items.variant.product.brand', 'items.variant.productColor', 'user', 'cancelledBy'])
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(self::POR_PAGINA, ['*'], 'pagina', $pagina);

        // Las compras canceladas se siguen mostrando en el historial (por trazabilidad),
        // pero no cuentan como gasto real.
        $vigentes = Purchase::whereNull('cancelled_at');
        $numeroCompras = (clone $vigentes)->count();
        $gastado = (float) (clone $vigentes)->sum('total');

        return Inertia::render('Admin/Compras/Historial', [
            'compras' => $paginador->getCollection()->map(fn ($p) => $this->mapCompra($p))->values(),
            'paginacion' => ['pagina' => $paginador->currentPage(), 'total' => $paginador->total(), 'porPagina' => self::POR_PAGINA],
            'filtros' => ['buscar' => $buscar],
            'totalGeneral' => Purchase::count(),
            'stats' => [
                'totalCompras' => $numeroCompras,
                'totalGastado' => $gastado,
                'promedioCompra' => $numeroCompras > 0 ? $gastado / $numeroCompras : 0,
            ],
        ]);
    }

    private function comprasFiltradas(string $buscar)
    {
        $query = Purchase::query();

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('supplier', 'like', "%{$buscar}%")
                    ->orWhere('invoice_number', 'like', "%{$buscar}%")
                    ->orWhereHas('items.variant.product', fn ($p) => $p->where('name', 'like', "%{$buscar}%"));

                // "C-00012", "c12" o "12" buscan por número de compra.
                if (preg_match('/^c?-?0*(\d+)$/i', $buscar, $m)) {
                    $q->orWhere('id', (int) $m[1]);
                }
            });
        }

        return $query;
    }

    // Revierte una compra completa: descuenta de las variantes el stock que esa compra
    // había agregado, y la marca como cancelada (no se borra, queda en el historial).
    // Solo se puede cancelar si nada de esa compra ya fue vendido — si ya se vendió,
    // hay que cancelar esa venta primero para no dejar el stock/valor de inventario mal.
    // Solo un administrador puede hacerlo, ya que afecta cifras de gasto ya reportadas.
    public function cancel(Purchase $compra)
    {
        if (Auth::guard('web')->user()->role !== 'admin') {
            abort(403, 'Solo un administrador puede cancelar una compra.');
        }

        if ($compra->cancelled_at !== null) {
            return back()->withErrors(['error' => 'Esta compra ya estaba cancelada.']);
        }

        $compra->load('items');

        $yaVendido = $compra->items->contains(fn ($item) => $item->quantity_remaining < $item->quantity);
        if ($yaVendido) {
            return back()->withErrors(['error' => 'No se puede cancelar: parte de esta compra ya fue vendida. Cancela esa venta primero.']);
        }

        DB::transaction(function () use ($compra) {
            foreach ($compra->items as $item) {
                $variant = ProductVariant::find($item->product_variant_id);

                if ($variant) {
                    $variant->decrement('stock', $item->quantity);
                    $variant->product->decrement('stock', $item->quantity);
                }

                $item->update(['quantity_remaining' => 0]);
            }

            $compra->update([
                'cancelled_at' => now(),
                'cancelled_by' => Auth::guard('web')->id(),
            ]);
        });

        return back()->with('success', 'Compra cancelada. El stock fue revertido.');
    }

    public function reportes(Request $request)
    {
        $r = $this->datosReporte($request);

        return Inertia::render('Admin/Compras/Reportes', [
            'filtros' => $r['filtros'],
            'proveedores' => Purchase::whereNotNull('supplier')->where('supplier', '!=', '')->distinct()->orderBy('supplier')->pluck('supplier'),
            'categorias' => Category::orderBy('name')->pluck('name'),
            'stats' => $r['stats'],
            'serie' => $r['serie'],
            'anterior' => $r['anterior'],
            'porCategoria' => $r['porCategoria'],
            'porProveedor' => $r['porProveedor'],
            'porMarca' => $r['porMarca'],
            'topProductos' => $r['productos']->take(10)->values(),
            'totalProductos' => $r['productos']->count(),
        ]);
    }

    // Descarga en Excel el mismo reporte que se ve en pantalla (mismos filtros), pero completo: todos los productos
    // y el detalle línea por línea de cada compra.
    public function exportarReporte(Request $request)
    {
        $r = $this->datosReporte($request);
        $f = $r['filtros'];
        $negocio = BusinessSetting::current();

        $periodo = match (true) {
            (bool) ($f['desde'] && $f['hasta']) => \Illuminate\Support\Carbon::parse($f['desde'])->format('d/m/Y').' al '.\Illuminate\Support\Carbon::parse($f['hasta'])->format('d/m/Y'),
            (bool) $f['desde'] => 'Desde el '.\Illuminate\Support\Carbon::parse($f['desde'])->format('d/m/Y'),
            (bool) $f['hasta'] => 'Hasta el '.\Illuminate\Support\Carbon::parse($f['hasta'])->format('d/m/Y'),
            default => 'Todo el historial',
        };

        $total = (float) $r['stats']['totalInvertido'];
        $porcentaje = fn ($valor) => $total > 0 ? $valor / $total : 0;

        // En el total, N° de compras es el real: una compra con prendas de dos categorías (o marcas) se cuenta en cada una.
        $agrupada = fn (string $nombre, string $titulo, $filas) => ExcelReport::tabla(
            $nombre,
            [['#', 'entero', 5], [$titulo, 'texto', 32], ['N° de compras', 'entero', 14], ['Unidades', 'entero', 12], ['Total invertido', 'dinero', 18], ['% del total', 'porcentaje', 12]],
            $filas->values()->map(fn ($x, $i) => [$i + 1, $x['label'], $x['compras'], $x['unidades'], round($x['total'], 2), $porcentaje($x['total'])])->all(),
            [null, 'TOTAL', $r['stats']['numeroCompras'], $filas->sum('unidades'), round($filas->sum('total'), 2), $total > 0 ? 1 : 0],
        );

        $hojas = [
            ExcelReport::resumen('Resumen', 'Reporte de compras · '.$negocio->name, [
                ['Período', $periodo],
                ['Proveedor', $f['proveedor'] ?: 'Todos'],
                ['Categoría', $f['categoria'] ?: 'Todas'],
                ['Generado', now()->format('d/m/Y H:i')],
                ['# Totales'],
                ['Total invertido', $total, 'dinero'],
                ['N° de compras', $r['stats']['numeroCompras'], 'entero'],
                ['Unidades compradas', $r['stats']['totalUnidades'], 'entero'],
                ['Promedio por compra', round($r['stats']['promedioPorCompra'], 2), 'dinero'],
            ]),
            $agrupada('Por categoría', 'Categoría', $r['porCategoria']),
            $agrupada('Por proveedor', 'Proveedor', $r['porProveedor']),
            $agrupada('Por marca', 'Marca', $r['porMarca']),
            ExcelReport::tabla(
                'Productos',
                [['#', 'entero', 5], ['Producto', 'texto', 30], ['Marca', 'texto', 18], ['Categoría', 'texto', 18], ['N° de compras', 'entero', 14], ['Unidades', 'entero', 12], ['Costo promedio', 'dinero', 16], ['Total invertido', 'dinero', 18], ['% del total', 'porcentaje', 12]],
                $r['productos']->values()->map(fn ($x, $i) => [$i + 1, $x['label'], $x['marca'], $x['categoria'], $x['numeroCompras'], $x['unidades'], round($x['costoPromedio'], 2), round($x['total'], 2), $porcentaje($x['total'])])->all(),
                [null, 'TOTAL', null, null, $r['stats']['numeroCompras'], $r['productos']->sum('unidades'), null, round($r['productos']->sum('total'), 2), $total > 0 ? 1 : 0],
            ),
            ExcelReport::tabla(
                'Detalle',
                [['Fecha', 'fecha', 12], ['N° compra', 'texto', 12], ['Proveedor', 'texto', 34], ['Factura', 'texto', 14], ['Producto', 'texto', 28], ['Marca', 'texto', 16], ['Categoría', 'texto', 16], ['Color', 'texto', 14], ['Talla', 'texto', 9], ['Cantidad', 'entero', 12], ['Costo de compra (unid.)', 'dinero', 20], ['Subtotal', 'dinero', 16]],
                $r['detalle']->all(),
                [null, null, null, null, null, null, null, null, 'TOTAL', $r['detalle']->sum(9), null, round($r['detalle']->sum(11), 2)],
            ),
        ];

        return ExcelReport::descargar('reporte-compras-'.now()->format('Y-m-d').'.xlsx', $hojas, 'Reporte de compras');
    }

    // Líneas de compra del reporte. Las compras canceladas no cuentan: esa plata no se invirtió.
    private function itemsReporte(?string $desde, ?string $hasta, ?string $proveedor, ?string $categoria)
    {
        $itemsQuery = PurchaseItem::with(['purchase', 'variant.product.category', 'variant.product.brand', 'variant.productColor'])
            ->whereHas('purchase', function ($q) use ($desde, $hasta, $proveedor) {
                $q->whereNull('cancelled_at');
                if ($desde) {
                    $q->whereDate('purchase_date', '>=', $desde);
                }
                if ($hasta) {
                    $q->whereDate('purchase_date', '<=', $hasta);
                }
                if ($proveedor) {
                    $q->where('supplier', $proveedor);
                }
            });

        if ($categoria) {
            $itemsQuery->whereHas('variant.product.category', fn ($q) => $q->where('name', $categoria));
        }

        return $itemsQuery->get();
    }

    // Datos del reporte de compras, compartidos por la pantalla y la descarga de Excel. Las compras canceladas no
    // cuentan: esa plata no se invirtió.
    private function datosReporte(Request $request): array
    {
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');
        $proveedor = $request->query('proveedor');
        $categoria = $request->query('categoria');

        $items = $this->itemsReporte($desde, $hasta, $proveedor, $categoria);

        $numeroCompras = $items->pluck('purchase_id')->unique()->count();
        $totalInvertido = $items->sum('subtotal');

        $agrupar = fn ($clave, $etiqueta) => $items->groupBy($clave)
            ->map(fn ($group) => [
                'label' => $etiqueta($group),
                'total' => (float) $group->sum('subtotal'),
                'unidades' => (int) $group->sum('quantity'),
                'compras' => $group->pluck('purchase_id')->unique()->count(),
            ])
            ->sortByDesc('total')->values();

        $porCategoria = $agrupar(fn ($i) => $i->variant->product->category->name ?? 'Sin categoría', fn ($g) => $g->first()->variant->product->category->name ?? 'Sin categoría');

        // Se agrupa por supplier_id cuando la compra está vinculada a un proveedor real,
        // para no confundir a dos proveedores distintos que se escribieron con el mismo nombre.
        $porProveedor = $agrupar(fn ($i) => $this->claveProveedor($i->purchase), fn ($g) => $g->first()->purchase->supplier ?: 'Sin proveedor');

        $porMarca = $agrupar(fn ($i) => $i->variant->product->brand->name ?? 'Sin marca', fn ($g) => $g->first()->variant->product->brand->name ?? 'Sin marca');

        $productos = $items->groupBy(fn ($i) => $i->variant->product->id)
            ->map(function ($group) {
                $unidades = $group->sum('quantity');
                $total = $group->sum('subtotal');
                $producto = $group->first()->variant->product;

                return [
                    'label' => $producto->name,
                    'marca' => $producto->brand->name ?? '-',
                    'categoria' => $producto->category->name ?? 'Sin categoría',
                    'unidades' => (int) $unidades,
                    'numeroCompras' => $group->pluck('purchase_id')->unique()->count(),
                    'costoPromedio' => $unidades > 0 ? $total / $unidades : 0,
                    'total' => (float) $total,
                ];
            })
            ->sortByDesc('unidades')->values();

        // Una fila por línea de compra, para el Excel (más reciente primero).
        $detalle = $items->sortByDesc(fn ($i) => $i->purchase->purchase_date->format('Ymd').str_pad($i->purchase_id, 8, '0', STR_PAD_LEFT))
            ->map(fn ($i) => [
                $i->purchase->purchase_date,
                'C-'.str_pad($i->purchase_id, 5, '0', STR_PAD_LEFT),
                $i->purchase->supplier ?: 'Sin proveedor',
                $i->purchase->invoice_number,
                $i->variant->product->name,
                $i->variant->product->brand->name ?? '-',
                $i->variant->product->category->name ?? 'Sin categoría',
                $i->variant->productColor->name ?? 'Sin color',
                $i->variant->size,
                (int) $i->quantity,
                (float) $i->unit_cost,
                (float) $i->subtotal,
            ])->values();

        return [
            'filtros' => compact('desde', 'hasta', 'proveedor', 'categoria'),
            'stats' => [
                'totalInvertido' => (float) $totalInvertido,
                'numeroCompras' => $numeroCompras,
                'totalUnidades' => (int) $items->sum('quantity'),
                'promedioPorCompra' => $numeroCompras > 0 ? $totalInvertido / $numeroCompras : 0,
            ],
            'serie' => ReportePeriodo::serie(
                $items->groupBy(fn ($i) => $i->purchase->purchase_date->format('Y-m-d')),
                $desde,
                $hasta,
                fn ($g) => ['total' => round($g->sum('subtotal'), 2), 'compras' => $g->pluck('purchase_id')->unique()->count()],
                ['total' => 0, 'compras' => 0],
            ),
            'anterior' => ReportePeriodo::anterior($desde, $hasta, function ($d, $h) use ($proveedor, $categoria) {
                $previos = $this->itemsReporte($d, $h, $proveedor, $categoria);

                return ['total' => (float) $previos->sum('subtotal'), 'compras' => $previos->pluck('purchase_id')->unique()->count()];
            }),
            'porCategoria' => $porCategoria,
            'porProveedor' => $porProveedor,
            'porMarca' => $porMarca,
            'productos' => $productos,
            'detalle' => $detalle,
        ];
    }

    public function lotes(ProductVariant $variant)
    {
        $lotes = PurchaseItem::with('purchase')
            ->where('product_variant_id', $variant->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'numeroCompra' => 'C-' . str_pad($item->purchase_id, 5, '0', STR_PAD_LEFT),
                'fecha' => $item->purchase->purchase_date->format('d/m/Y'),
                'proveedor' => $item->purchase->supplier,
                'cantidad' => $item->quantity,
                'cantidadRestante' => $item->quantity_remaining,
                'costoUnitario' => (float) $item->unit_cost,
            ]);

        return response()->json($lotes);
    }

    public function searchProducts(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $categoria = $request->query('categoria');
        $marca = $request->query('marca');

        // Sin texto y sin categoría/marca elegidas: no hay nada útil que sugerir todavía.
        if (mb_strlen($q) < 2 && !$categoria && !$marca) {
            return response()->json([]);
        }

        $query = Product::with(['category', 'brand']);

        if ($q) {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                    ->orWhereHas('brand', fn ($bq) => $bq->where('name', 'like', "%{$q}%"));
            });
        }

        if ($categoria) {
            $query->whereHas('category', fn ($qq) => $qq->where('name', $categoria));
        }

        if ($marca) {
            $query->whereHas('brand', fn ($qq) => $qq->where('name', $marca));
        }

        $products = $query->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->name,
                'marca' => $p->brand->name ?? null,
                'categoria' => $p->category->name ?? null,
                'stock' => $p->stock,
            ]);

        return response()->json($products);
    }

    // Sugerencias de proveedores ya registrados, por nombre o por documento.
    public function searchSuppliers(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Supplier::where('name', 'like', "%{$q}%")
                ->orWhere('document_number', 'like', "%{$q}%")
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name', 'document_type', 'document_number'])
        );
    }

    // Un RUC (11 dígitos) o DNI (8) escrito en Proveedor: primero se mira si ya es proveedor; si no, se consulta
    // SUNAT/RENIEC solo para traer el nombre. No se crea nada aquí: el proveedor nuevo se registra al guardar la compra.
    public function buscarProveedorDocumento(Request $request, ApiPeruService $apiPeru)
    {
        $request->validate(['numero' => 'required|digits_between:8,11']);

        $numero = $request->numero;
        $tipo = match (true) {
            preg_match('/^\d{8}$/', $numero) === 1 => 'DNI',
            preg_match('/^(10|20)\d{9}$/', $numero) === 1 => 'RUC',
            default => null,
        };

        if (!$tipo) {
            return response()->json(['message' => 'Escribe un DNI de 8 dígitos o un RUC de 11 (empieza con 10 o 20).'], 422);
        }

        $existente = Supplier::where('document_number', $numero)->first(['id', 'name', 'document_type', 'document_number']);
        if ($existente) {
            return response()->json(['existe' => true, 'proveedor' => $existente]);
        }

        try {
            $datos = $tipo === 'DNI' ? $apiPeru->dni($numero) : $apiPeru->ruc($numero);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'No se pudo consultar el documento. Escribe el nombre a mano.'], 422);
        }

        return response()->json(['existe' => false, 'tipo' => $tipo, 'numero' => $numero, 'nombre' => $datos['nombre']]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha' => 'required|date',
            'tienda' => 'nullable|string|max:255',
            'proveedorId' => 'nullable|integer|exists:suppliers,id',
            'proveedorDocTipo' => 'nullable|in:DNI,RUC',
            'proveedorDocNumero' => 'nullable|digits_between:8,11',
            'factura' => 'nullable|string|max:255',
            'notas' => 'nullable|string',
            'prendas' => 'required|array|min:1',
            'prendas.*.productId' => 'nullable|integer|exists:products,id',
            'prendas.*.nombre' => 'required|string|max:255',
            'prendas.*.categoria' => 'required|string|max:255',
            'prendas.*.marca' => 'required|string|max:255',
            'prendas.*.talla' => 'required|string|max:20',
            'prendas.*.colorNombre' => 'required|string|max:50',
            'prendas.*.color' => 'nullable|string|max:7',
            'prendas.*.cantidad' => 'required|integer|min:1',
            'prendas.*.precioCompra' => 'required|numeric|min:0',
            'prendas.*.precioVenta' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $total = collect($validated['prendas'])->sum(fn ($p) => $p['cantidad'] * $p['precioCompra']);

            // Igual que con el cliente en Ventas: si escriben un proveedor a mano, se
            // reutiliza uno ya registrado con ese nombre o se crea como nuevo, para que
            // toda compra con proveedor quede vinculada a un registro real.
            $proveedorVinculado = null;

            $nombreProveedor = trim((string) ($validated['tienda'] ?? ''));
            $docNumero = $validated['proveedorDocNumero'] ?? null;
            $docTipo = $docNumero ? ($validated['proveedorDocTipo'] ?? (strlen($docNumero) === 8 ? 'DNI' : 'RUC')) : null;

            // Orden de búsqueda: el elegido de la lista, luego el mismo documento, luego el mismo nombre.
            if (!empty($validated['proveedorId'])) {
                $proveedorVinculado = Supplier::find($validated['proveedorId']);
            }

            if (!$proveedorVinculado && $docNumero) {
                $proveedorVinculado = Supplier::where('document_number', $docNumero)->first();
            }

            if (!$proveedorVinculado && $nombreProveedor !== '') {
                $proveedorVinculado = Supplier::whereRaw(self::NAME_MATCH, [mb_strtolower($nombreProveedor)])->first();
            }

            if (!$proveedorVinculado && $nombreProveedor !== '') {
                $proveedorVinculado = Supplier::create([
                    'name' => $nombreProveedor,
                    'document_type' => $docTipo,
                    'document_number' => $docNumero,
                ]);
            } elseif ($proveedorVinculado && $docNumero && !$proveedorVinculado->document_number) {
                // Ya existía solo con nombre: se le completa el documento.
                $proveedorVinculado->update(['document_type' => $docTipo, 'document_number' => $docNumero]);
            }

            $purchase = Purchase::create([
                'user_id' => Auth::guard('web')->id(),
                'purchase_date' => $validated['fecha'],
                'supplier' => $proveedorVinculado->name ?? null,
                'supplier_id' => $proveedorVinculado->id ?? null,
                'invoice_number' => $validated['factura'] ?? null,
                'notes' => $validated['notas'] ?? null,
                'total' => $total,
            ]);

            foreach ($validated['prendas'] as $item) {
                // La marca es independiente de la categoría: se busca globalmente por nombre normalizado.
                $brand = Brand::whereRaw(self::NAME_MATCH, [mb_strtolower(trim($item['marca']))])->first();

                // Si vino de una selección explícita del buscador, reutiliza ese producto tal cual.
                $product = !empty($item['productId'])
                    ? Product::find($item['productId'])
                    : null;

                // Red de seguridad: si escribieron el nombre a mano, buscar por coincidencia
                // normalizada (sin importar mayúsculas/espacios) antes de crear uno nuevo.
                if (!$product && $brand) {
                    $product = Product::whereRaw(self::NAME_MATCH, [mb_strtolower(trim($item['nombre']))])
                        ->where('brand_id', $brand->id)
                        ->first();
                }

                if (!$product) {
                    // Solo se crean/usan categoría y marca cuando realmente se está creando un producto nuevo.
                    if (!$brand) {
                        // Una marca casi igual a otra ya registrada ("Nikes" y "Nike") es casi seguro un error.
                        if ($parecida = NameMatcher::similar(Brand::class, $item['marca'])) {
                            throw ValidationException::withMessages([
                                'prendas' => "La marca «{$item['marca']}» se parece a «{$parecida->name}». Elige esa de la lista.",
                            ]);
                        }

                        $brand = Brand::firstOrCreate(
                            ['slug' => Str::slug($item['marca'])],
                            ['name' => trim($item['marca']), 'active' => true]
                        );
                    }

                    // Las categorías no se crean escribiendo: se eligen de la lista (o se crean a propósito
                    // desde el formulario con "Nueva categoría" / en Categorías y Marcas).
                    $category = Category::whereRaw(self::NAME_MATCH, [mb_strtolower(trim($item['categoria']))])->first();

                    if (!$category) {
                        throw ValidationException::withMessages([
                            'prendas' => "La categoría «{$item['categoria']}» no existe. Elígela de la lista o créala antes.",
                        ]);
                    }

                    $category->brands()->syncWithoutDetaching([$brand->id]);

                    $product = Product::create([
                        'name' => trim($item['nombre']),
                        'brand_id' => $brand->id,
                        'category_id' => $category->id,
                        'description' => '',
                        'sku' => strtoupper(Str::slug($item['nombre'] . '-' . $item['marca'])) . '-' . Str::random(4),
                        'price' => $item['precioVenta'],
                        'cost' => $item['precioCompra'],
                        'stock' => 0,
                        'image_url' => null,
                        'active' => true,
                    ]);
                } else {
                    // El producto ya existe: su categoría/marca originales no se tocan aunque hayan
                    // escrito algo distinto por error; solo se actualizan precio y costo.
                    $product->update([
                        'price' => $item['precioVenta'],
                        'cost' => $item['precioCompra'],
                    ]);
                }

                // El color es propio de cada Modelo (no se comparte entre productos). Se busca por
                // nombre normalizado dentro del producto; si no existe se crea sin foto todavía —
                // la foto se agrega después desde Catálogo.
                $productColor = ProductColor::where('product_id', $product->id)
                    ->whereRaw(self::NAME_MATCH, [mb_strtolower(trim($item['colorNombre']))])
                    ->first();

                if (!$productColor) {
                    $productColor = ProductColor::create([
                        'product_id' => $product->id,
                        'name' => $item['colorNombre'],
                        'hex' => $item['color'] ?? null,
                        'image_url' => null,
                    ]);
                }

                $variant = ProductVariant::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'size' => $item['talla'],
                        'product_color_id' => $productColor->id,
                    ],
                    [
                        'sku' => strtoupper($product->sku . '-' . $item['talla'] . '-' . $item['colorNombre']) . '-' . Str::random(3),
                        'stock' => 0,
                        'cost' => $item['precioCompra'],
                        'price' => $item['precioVenta'],
                    ]
                );

                // El costo y el precio de venta son propios de cada talla/color — dos variantes del
                // mismo modelo pueden venderse a precios distintos (ej. un color en edición limitada).
                // El "costo" es solo referencial (el del lote más reciente); el valor real de
                // inventario se calcula por lote en index().
                $variant->update(['cost' => $item['precioCompra'], 'price' => $item['precioVenta']]);
                $variant->increment('stock', $item['cantidad']);
                $product->increment('stock', $item['cantidad']);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $item['cantidad'],
                    'quantity_remaining' => $item['cantidad'],
                    'unit_cost' => $item['precioCompra'],
                    'unit_price' => $item['precioVenta'],
                    'subtotal' => $item['cantidad'] * $item['precioCompra'],
                ]);
            }

            return $purchase;
        });

        return redirect()->route('admin.purchases.index')->with('success', 'Compra registrada correctamente.');
    }

    private function claveProveedor(Purchase $purchase): string
    {
        if ($purchase->supplier_id) {
            return "id:{$purchase->supplier_id}";
        }

        return 'nombre:' . ($purchase->supplier ?: 'Sin proveedor');
    }
}
