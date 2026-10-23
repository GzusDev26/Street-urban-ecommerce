<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Support\NameMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PurchaseController extends Controller
{
    private const NAME_MATCH = 'LOWER(TRIM(name)) = ?';

    public function index()
    {
        $variants = ProductVariant::with([
                'product.category',
                'product.brand',
                'productColor',
                'purchaseItems' => fn ($q) => $q->with('purchase')->latest('created_at'),
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($variant) {
                // Último lote registrado de esta variante: se usa para sugerir el mismo
                // proveedor al "Reabastecer" (una variante puede haberse comprado a más
                // de un proveedor en el tiempo, así que es solo una sugerencia editable).
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
            });

        // Valor real de inventario: suma de lo que queda de cada lote a su costo real (FIFO),
        // no cantidad total x último costo, ya que un mismo producto puede tener lotes a precios distintos.
        $valorInventario = PurchaseItem::where('quantity_remaining', '>', 0)
            ->get()
            ->sum(fn ($item) => $item->quantity_remaining * $item->unit_cost);

        return Inertia::render('Admin/Compras/Index', [
            'variants' => $variants,
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
                'totalVariantes' => $variants->count(),
                'sinStock' => $variants->where('stock', 0)->count(),
                'valorInventario' => $valorInventario,
            ],
        ]);
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
                        'imagenUrl' => $c->image_url ? \Illuminate\Support\Facades\Storage::url($c->image_url) : null,
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

    public function historial()
    {
        $compras = Purchase::with(['items.variant.product.brand', 'items.variant.productColor', 'user', 'cancelledBy'])
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($p) => $this->mapCompra($p));

        // Las compras canceladas se siguen mostrando en el historial (por trazabilidad),
        // pero no cuentan como gasto real.
        $vigentes = $compras->reject('cancelada');

        return Inertia::render('Admin/Compras/Historial', [
            'compras' => $compras,
            'stats' => [
                'totalCompras' => $vigentes->count(),
                'totalGastado' => $vigentes->sum('total'),
                'promedioCompra' => $vigentes->count() > 0 ? $vigentes->sum('total') / $vigentes->count() : 0,
            ],
        ]);
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
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');
        $proveedor = $request->query('proveedor');
        $categoria = $request->query('categoria');

        $itemsQuery = PurchaseItem::with(['purchase', 'variant.product.category', 'variant.product.brand'])
            ->whereHas('purchase', function ($q) use ($desde, $hasta, $proveedor) {
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

        $items = $itemsQuery->get();

        $numeroCompras = $items->pluck('purchase_id')->unique()->count();
        $totalInvertido = $items->sum('subtotal');

        $porCategoria = $items->groupBy(fn ($i) => $i->variant->product->category->name ?? 'Sin categoría')
            ->map(fn ($group, $nombre) => [
                'label' => $nombre,
                'total' => $group->sum('subtotal'),
                'unidades' => $group->sum('quantity'),
                'compras' => $group->pluck('purchase_id')->unique()->count(),
            ])
            ->sortByDesc('total')->values();

        // Se agrupa por supplier_id cuando la compra está vinculada a un proveedor real,
        // para no confundir a dos proveedores distintos que se escribieron con el mismo nombre.
        $porProveedor = $items->groupBy(fn ($i) => $this->claveProveedor($i->purchase))
            ->map(fn ($group) => [
                'label' => $group->first()->purchase->supplier ?: 'Sin proveedor',
                'total' => $group->sum('subtotal'),
                'unidades' => $group->sum('quantity'),
                'compras' => $group->pluck('purchase_id')->unique()->count(),
            ])
            ->sortByDesc('total')->values();

        $porMarca = $items->groupBy(fn ($i) => $i->variant->product->brand->name ?? 'Sin marca')
            ->map(fn ($group, $nombre) => [
                'label' => $nombre,
                'total' => $group->sum('subtotal'),
                'unidades' => $group->sum('quantity'),
                'compras' => $group->pluck('purchase_id')->unique()->count(),
            ])
            ->sortByDesc('total')->values();

        $topProductos = $items->groupBy(fn ($i) => $i->variant->product->name)
            ->map(function ($group, $nombre) {
                $unidades = $group->sum('quantity');
                $total = $group->sum('subtotal');

                return [
                    'label' => $nombre,
                    'marca' => $group->first()->variant->product->brand->name ?? '-',
                    'categoria' => $group->first()->variant->product->category->name ?? 'Sin categoría',
                    'unidades' => $unidades,
                    'numeroCompras' => $group->pluck('purchase_id')->unique()->count(),
                    'costoPromedio' => $unidades > 0 ? $total / $unidades : 0,
                    'total' => $total,
                ];
            })
            ->sortByDesc('unidades')->values()->take(10);

        return Inertia::render('Admin/Compras/Reportes', [
            'filtros' => compact('desde', 'hasta', 'proveedor', 'categoria'),
            'proveedores' => Purchase::whereNotNull('supplier')->where('supplier', '!=', '')->distinct()->orderBy('supplier')->pluck('supplier'),
            'categorias' => Category::orderBy('name')->pluck('name'),
            'stats' => [
                'totalInvertido' => $totalInvertido,
                'numeroCompras' => $numeroCompras,
                'totalUnidades' => $items->sum('quantity'),
                'promedioPorCompra' => $numeroCompras > 0 ? $totalInvertido / $numeroCompras : 0,
            ],
            'porCategoria' => $porCategoria,
            'porProveedor' => $porProveedor,
            'porMarca' => $porMarca,
            'topProductos' => $topProductos,
        ]);
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha' => 'required|date',
            'tienda' => 'nullable|string|max:255',
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

            if (!empty($validated['tienda'])) {
                $nombreProveedor = trim($validated['tienda']);

                $proveedorVinculado = Supplier::whereRaw(self::NAME_MATCH, [mb_strtolower($nombreProveedor)])->first();

                if (!$proveedorVinculado) {
                    $proveedorVinculado = Supplier::create(['name' => $nombreProveedor]);
                }
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
