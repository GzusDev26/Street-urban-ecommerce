<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemReturn;
use App\Services\ApiPeruService;
use App\Services\InventoryService;
use App\Support\ExcelReport;
use App\Support\ReportePeriodo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Throwable;

class SaleController extends Controller
{
    private function mapVenta(Sale $v): array
    {
        // "Neto" = descontando lo que ya se devolvió de cada línea. Es lo que de verdad
        // sigue contando como ingreso/prenda vendida — el total original se conserva solo
        // como comprobante de lo que se facturó ese día (ver boleta).
        $totalNeto = $v->items->sum(fn ($item) => $item->net_subtotal);
        $prendasDevueltas = $v->items->sum('returned_quantity');

        return [
            'id' => $v->id,
            'numero' => 'V-' . str_pad($v->id, 5, '0', STR_PAD_LEFT),
            'fecha' => $v->sale_date->format('d/m/Y'),
            'cliente' => $v->customer_name,
            'clienteId' => $v->customer_id,
            'clienteDocumento' => $v->relationLoaded('customer') && $v->customer?->document_number ? "{$v->customer->document_type} {$v->customer->document_number}" : null,
            'notas' => $v->notes,
            'total' => (float) $v->total,
            'totalNeto' => $totalNeto,
            'registradoPor' => $v->user->name ?? null,
            'totalPrendas' => $v->items->sum('quantity'),
            'prendasDevueltas' => $prendasDevueltas,
            'cancelada' => $v->cancelled_at !== null,
            'canceladaPor' => $v->cancelledBy->name ?? null,
            'items' => $v->items->map(fn ($item) => [
                'id' => $item->id,
                'producto' => $item->variant->product->name,
                'marca' => $item->variant->product->brand->name ?? '-',
                'talla' => $item->variant->size,
                'color' => $item->variant->productColor->name ?? '-',
                'colorHex' => $item->variant->productColor->hex ?? null,
                'cantidad' => $item->quantity,
                'cantidadDevuelta' => $item->returned_quantity,
                'pendienteDevolucion' => $item->net_quantity,
                'precioUnitario' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'subtotalNeto' => $item->net_subtotal,
            ]),
        ];
    }

    private const POR_PAGINA = 15;

    // Historial de ventas: la búsqueda, el filtro de estado y la paginación se hacen aquí, en la base de datos,
    // para que la pantalla cargue solo las 15 ventas que muestra (y no todo el historial).
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $estado = in_array($request->query('estado'), ['vigentes', 'devoluciones', 'canceladas'], true) ? $request->query('estado') : 'todas';

        $filtrada = fn () => $this->ventasFiltradas($buscar, $estado);

        // Desde "Ver en Historial" (?venta=123) se abre la página donde está esa venta.
        $ventaId = (int) $request->query('venta');
        $pagina = max(1, (int) $request->query('pagina', 1));
        if ($ventaId && ! $request->has('pagina')) {
            $objetivo = Sale::find($ventaId);
            if ($objetivo && $filtrada()->whereKey($ventaId)->exists()) {
                $antes = $filtrada()->where(fn ($q) => $q
                    ->where('sale_date', '>', $objetivo->sale_date->toDateString())
                    ->orWhere(fn ($q2) => $q2->where('sale_date', $objetivo->sale_date->toDateString())->where('id', '>', $objetivo->id)))->count();
                $pagina = intdiv($antes, self::POR_PAGINA) + 1;
            }
        }

        $paginador = $filtrada()
            ->with(['items.variant.product.brand', 'items.variant.productColor', 'user', 'cancelledBy'])
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->paginate(self::POR_PAGINA, ['*'], 'pagina', $pagina);

        $ventas = $paginador->getCollection()->map(fn ($v) => $this->mapVenta($v))->values();

        // Total de cada día que aparece en esta página, sobre todas las ventas vigentes filtradas de ese día
        // (aunque el día continúe en otra página). Es neto de devoluciones.
        $fechas = $paginador->getCollection()->pluck('sale_date')->map->toDateString()->unique()->values();
        $totalesDia = $filtrada()->whereNull('cancelled_at')->whereIn('sale_date', $fechas)->with('items')->get()
            ->groupBy(fn ($v) => $v->sale_date->format('d/m/Y'))
            ->map(fn ($grupo) => ['cantidad' => $grupo->count(), 'total' => round($grupo->sum(fn ($v) => $v->items->sum(fn ($i) => $i->net_subtotal)), 2)]);

        // Cifras de arriba: todo el historial vigente (las canceladas se muestran por trazabilidad pero no cuentan).
        $vigentes = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')->whereNull('sales.cancelled_at');
        $ingresos = (float) (clone $vigentes)->sum(DB::raw('(sale_items.quantity - sale_items.returned_quantity) * sale_items.unit_price'));
        $prendas = (int) (clone $vigentes)->sum(DB::raw('sale_items.quantity - sale_items.returned_quantity'));
        $numeroVentas = Sale::whereNull('cancelled_at')->count();

        return Inertia::render('Admin/Ventas/Index', [
            'ventas' => $ventas,
            'totalesDia' => $totalesDia,
            'paginacion' => ['pagina' => $paginador->currentPage(), 'total' => $paginador->total(), 'porPagina' => self::POR_PAGINA],
            'filtros' => ['buscar' => $buscar, 'estado' => $estado],
            'conteos' => [
                'todas' => Sale::count(),
                'vigentes' => $numeroVentas,
                'devoluciones' => $this->ventasFiltradas('', 'devoluciones')->count(),
                'canceladas' => Sale::whereNotNull('cancelled_at')->count(),
            ],
            'stats' => [
                'totalVentas' => $numeroVentas,
                'totalIngresos' => $ingresos,
                'prendasVendidas' => $prendas,
                'promedioVenta' => $numeroVentas > 0 ? $ingresos / $numeroVentas : 0,
            ],
        ]);
    }

    private function ventasFiltradas(string $buscar, string $estado)
    {
        $query = Sale::query();

        match ($estado) {
            'vigentes' => $query->whereNull('cancelled_at'),
            'canceladas' => $query->whereNotNull('cancelled_at'),
            'devoluciones' => $query->whereNull('cancelled_at')->whereHas('items', fn ($q) => $q->where('returned_quantity', '>', 0)),
            default => null,
        };

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('customer_name', 'like', "%{$buscar}%")
                    ->orWhereHas('items.variant.product', fn ($p) => $p->where('name', 'like', "%{$buscar}%"));

                // "V-00012", "v12" o "12" buscan por número de venta.
                if (preg_match('/^v?-?0*(\d+)$/i', $buscar, $m)) {
                    $q->orWhere('id', (int) $m[1]);
                }
            });
        }

        return $query;
    }

    public function create()
    {
        return Inertia::render('Admin/Ventas/Registrar', [
            'categorias' => Category::orderBy('name')->pluck('name'),
        ]);
    }

    public function boleta(Sale $venta)
    {
        $venta->load(['items.variant.product.brand', 'items.variant.productColor', 'user', 'customer']);

        return Inertia::render('Admin/Ventas/Boleta', [
            'venta' => $this->mapVenta($venta),
        ]);
    }

    // Mismos datos que la boleta, en JSON: el modal de "Registrar venta" la dibuja sin salir de la página.
    public function boletaDatos(Sale $venta)
    {
        $venta->load(['items.variant.product.brand', 'items.variant.productColor', 'user', 'customer']);

        return response()->json($this->mapVenta($venta));
    }

    public function reportes(Request $request)
    {
        $r = $this->datosReporte($request);

        return Inertia::render('Admin/Ventas/Reportes', [
            'filtros' => $r['filtros'],
            'clientes' => Sale::whereNotNull('customer_name')->where('customer_name', '!=', '')->distinct()->orderBy('customer_name')->pluck('customer_name'),
            'categorias' => Category::orderBy('name')->pluck('name'),
            'stats' => $r['stats'],
            'serie' => $r['serie'],
            'anterior' => $r['anterior'],
            'porCategoria' => $r['porCategoria'],
            'porCliente' => $r['porCliente'],
            'porMarca' => $r['porMarca'],
            'topProductos' => $r['productos']->take(10)->values(),
            'totalProductos' => $r['productos']->count(),
        ]);
    }

    // Descarga en Excel el mismo reporte que se ve en pantalla (mismos filtros), pero completo: todos los productos
    // y el detalle línea por línea de cada venta.
    public function exportarReporte(Request $request)
    {
        $r = $this->datosReporte($request);
        $f = $r['filtros'];
        $negocio = BusinessSetting::current();

        $periodo = match (true) {
            (bool) ($f['desde'] && $f['hasta']) => Carbon::parse($f['desde'])->format('d/m/Y').' al '.Carbon::parse($f['hasta'])->format('d/m/Y'),
            (bool) $f['desde'] => 'Desde el '.Carbon::parse($f['desde'])->format('d/m/Y'),
            (bool) $f['hasta'] => 'Hasta el '.Carbon::parse($f['hasta'])->format('d/m/Y'),
            default => 'Todo el historial',
        };

        $total = (float) $r['stats']['totalIngresos'];
        $porcentaje = fn ($valor) => $total > 0 ? $valor / $total : 0;

        // En el total, N° de ventas es el real: una venta con prendas de dos categorías (o marcas) se cuenta en cada una.
        $agrupada = fn (string $nombre, string $titulo, $filas) => ExcelReport::tabla(
            $nombre,
            [['#', 'entero', 5], [$titulo, 'texto', 32], ['N° de ventas', 'entero', 13], ['Unidades', 'entero', 12], ['Ingresos', 'dinero', 16], ['Utilidad', 'dinero', 16], ['% de ingresos', 'porcentaje', 14]],
            $filas->values()->map(fn ($x, $i) => [$i + 1, $x['label'], $x['ventas'], $x['unidades'], round($x['total'], 2), round($x['utilidad'], 2), $porcentaje($x['total'])])->all(),
            [null, 'TOTAL', $r['stats']['numeroVentas'], $filas->sum('unidades'), round($filas->sum('total'), 2), round($filas->sum('utilidad'), 2), $total > 0 ? 1 : 0],
        );

        $utilidad = (float) $r['stats']['utilidadTotal'];

        $hojas = [
            ExcelReport::resumen('Resumen', 'Reporte de ventas · '.$negocio->name, [
                ['Período', $periodo],
                ['Cliente', $f['cliente'] ?: 'Todos'],
                ['Categoría', $f['categoria'] ?: 'Todas'],
                ['Generado', now()->format('d/m/Y H:i')],
                ['# Totales (ventas canceladas excluidas, devoluciones descontadas)'],
                ['Ingresos', $total, 'dinero'],
                ['Utilidad', round($utilidad, 2), 'dinero'],
                ['Margen de utilidad', $total > 0 ? $utilidad / $total : 0, 'porcentaje'],
                ['N° de ventas', $r['stats']['numeroVentas'], 'entero'],
                ['Prendas vendidas', $r['stats']['totalUnidades'], 'entero'],
                ['Promedio por venta', round($r['stats']['promedioPorVenta'], 2), 'dinero'],
            ]),
            $agrupada('Por categoría', 'Categoría', $r['porCategoria']),
            $agrupada('Por cliente', 'Cliente', $r['porCliente']),
            $agrupada('Por marca', 'Marca', $r['porMarca']),
            ExcelReport::tabla(
                'Productos',
                [['#', 'entero', 5], ['Producto', 'texto', 30], ['Marca', 'texto', 18], ['Categoría', 'texto', 18], ['N° de ventas', 'entero', 13], ['Unidades', 'entero', 12], ['Precio promedio', 'dinero', 16], ['Ingresos', 'dinero', 16], ['Utilidad', 'dinero', 16], ['% de ingresos', 'porcentaje', 14]],
                $r['productos']->values()->map(fn ($x, $i) => [$i + 1, $x['label'], $x['marca'], $x['categoria'], $x['numeroVentas'], $x['unidades'], round($x['precioPromedio'], 2), round($x['total'], 2), round($x['utilidad'], 2), $porcentaje($x['total'])])->all(),
                [null, 'TOTAL', null, null, $r['stats']['numeroVentas'], $r['productos']->sum('unidades'), null, round($r['productos']->sum('total'), 2), round($r['productos']->sum('utilidad'), 2), $total > 0 ? 1 : 0],
            ),
            ExcelReport::tabla(
                'Detalle',
                [['Fecha', 'fecha', 12], ['N° venta', 'texto', 12], ['Cliente', 'texto', 34], ['Producto', 'texto', 28], ['Marca', 'texto', 16], ['Categoría', 'texto', 16], ['Color', 'texto', 14], ['Talla', 'texto', 9], ['Cantidad', 'entero', 12], ['Precio de venta (unid.)', 'dinero', 20], ['Costo de compra (unid.)', 'dinero', 20], ['Subtotal', 'dinero', 16], ['Utilidad', 'dinero', 16]],
                $r['detalle']->all(),
                [null, null, null, null, null, null, null, 'TOTAL', $r['detalle']->sum(8), null, null, round($r['detalle']->sum(11), 2), round($r['detalle']->sum(12), 2)],
            ),
        ];

        return ExcelReport::descargar('reporte-ventas-'.now()->format('Y-m-d').'.xlsx', $hojas, 'Reporte de ventas');
    }

    // Líneas de venta del reporte. Las ventas canceladas no fueron ingresos reales, así que no entran.
    private function itemsReporte(?string $desde, ?string $hasta, ?string $cliente, ?string $categoria)
    {
        $itemsQuery = SaleItem::with(['sale', 'variant.product.category', 'variant.product.brand', 'variant.productColor'])
            ->whereHas('sale', function ($q) use ($desde, $hasta, $cliente) {
                $q->whereNull('cancelled_at');
                if ($desde) {
                    $q->whereDate('sale_date', '>=', $desde);
                }
                if ($hasta) {
                    $q->whereDate('sale_date', '<=', $hasta);
                }
                if ($cliente) {
                    $q->where('customer_name', $cliente);
                }
            });

        if ($categoria) {
            $itemsQuery->whereHas('variant.product.category', fn ($q) => $q->where('name', $categoria));
        }

        return $itemsQuery->get();
    }

    // Datos del reporte de ventas, compartidos por la pantalla y la descarga de Excel.
    private function datosReporte(Request $request): array
    {
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');
        $cliente = $request->query('cliente');
        $categoria = $request->query('categoria');

        $items = $this->itemsReporte($desde, $hasta, $cliente, $categoria);

        // Todo el reporte trabaja en "neto" (cantidad y subtotal ya restando lo devuelto),
        // para que ingresos/utilidad no incluyan prendas que el cliente regresó.
        $utilidadDe = fn ($i) => ($i->unit_price - $i->unit_cost) * $i->net_quantity;
        $numeroVentas = $items->pluck('sale_id')->unique()->count();
        $totalIngresos = $items->sum('net_subtotal');
        $utilidadTotal = $items->sum($utilidadDe);

        $agrupar = fn ($clave, $etiqueta) => $items->groupBy($clave)
            ->map(fn ($group) => [
                'label' => $etiqueta($group),
                'total' => (float) $group->sum('net_subtotal'),
                'unidades' => (int) $group->sum('net_quantity'),
                'ventas' => $group->pluck('sale_id')->unique()->count(),
                'utilidad' => (float) $group->sum($utilidadDe),
            ])
            ->sortByDesc('total')->values();

        $porCategoria = $agrupar(fn ($i) => $i->variant->product->category->name ?? 'Sin categoría', fn ($g) => $g->first()->variant->product->category->name ?? 'Sin categoría');

        // Se agrupa por customer_id cuando la venta está vinculada a un cliente real, para
        // no confundir a dos personas distintas que escribieron el mismo nombre a mano.
        $porCliente = $agrupar(fn ($i) => $this->claveCliente($i->sale), fn ($g) => $g->first()->sale->customer_name ?: 'Sin cliente registrado');

        $porMarca = $agrupar(fn ($i) => $i->variant->product->brand->name ?? 'Sin marca', fn ($g) => $g->first()->variant->product->brand->name ?? 'Sin marca');

        $productos = $items->groupBy(fn ($i) => $i->variant->product->id)
            ->map(function ($group) use ($utilidadDe) {
                $unidades = $group->sum('net_quantity');
                $total = $group->sum('net_subtotal');
                $producto = $group->first()->variant->product;

                return [
                    'label' => $producto->name,
                    'marca' => $producto->brand->name ?? '-',
                    'categoria' => $producto->category->name ?? 'Sin categoría',
                    'unidades' => (int) $unidades,
                    'numeroVentas' => $group->pluck('sale_id')->unique()->count(),
                    'precioPromedio' => $unidades > 0 ? $total / $unidades : 0,
                    'total' => (float) $total,
                    'utilidad' => (float) $group->sum($utilidadDe),
                ];
            })
            ->sortByDesc('unidades')->values();

        // Una fila por línea de venta, para el Excel (más reciente primero). Las líneas totalmente devueltas no se listan.
        $detalle = $items->filter(fn ($i) => $i->net_quantity > 0)
            ->sortByDesc(fn ($i) => $i->sale->sale_date->format('Ymd').str_pad($i->sale_id, 8, '0', STR_PAD_LEFT))
            ->map(fn ($i) => [
                $i->sale->sale_date,
                'V-'.str_pad($i->sale_id, 5, '0', STR_PAD_LEFT),
                $i->sale->customer_name ?: 'Sin cliente registrado',
                $i->variant->product->name,
                $i->variant->product->brand->name ?? '-',
                $i->variant->product->category->name ?? 'Sin categoría',
                $i->variant->productColor->name ?? 'Sin color',
                $i->variant->size,
                (int) $i->net_quantity,
                (float) $i->unit_price,
                (float) $i->unit_cost,
                (float) $i->net_subtotal,
                (float) $utilidadDe($i),
            ])->values();

        return [
            'filtros' => compact('desde', 'hasta', 'cliente', 'categoria'),
            'stats' => [
                'totalIngresos' => (float) $totalIngresos,
                'numeroVentas' => $numeroVentas,
                'totalUnidades' => (int) $items->sum('net_quantity'),
                'promedioPorVenta' => $numeroVentas > 0 ? $totalIngresos / $numeroVentas : 0,
                'utilidadTotal' => (float) $utilidadTotal,
            ],
            'serie' => ReportePeriodo::serie(
                $items->groupBy(fn ($i) => $i->sale->sale_date->format('Y-m-d')),
                $desde,
                $hasta,
                fn ($g) => ['ingresos' => round($g->sum('net_subtotal'), 2), 'utilidad' => round($g->sum($utilidadDe), 2), 'ventas' => $g->pluck('sale_id')->unique()->count()],
                ['ingresos' => 0, 'utilidad' => 0, 'ventas' => 0],
            ),
            'anterior' => ReportePeriodo::anterior($desde, $hasta, function ($d, $h) use ($cliente, $categoria, $utilidadDe) {
                $previos = $this->itemsReporte($d, $h, $cliente, $categoria);

                return ['ingresos' => (float) $previos->sum('net_subtotal'), 'utilidad' => (float) $previos->sum($utilidadDe), 'ventas' => $previos->pluck('sale_id')->unique()->count()];
            }),
            'porCategoria' => $porCategoria,
            'porCliente' => $porCliente,
            'porMarca' => $porMarca,
            'productos' => $productos,
            'detalle' => $detalle,
        ];
    }

    // Solo se pueden vender variantes que ya existen con stock disponible — a diferencia de
    // Compras, aquí no se crean productos/colores nuevos.
    // Busca MODELOS con stock y devuelve, de cada uno, todas sus variantes (color × talla) disponibles:
    // la pantalla muestra una tarjeta por modelo y una cuadrícula de colores × tallas para elegir.
    // Se busca por nombre del modelo, marca o color, y por categoría.
    public function searchVariants(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $categoria = $request->query('categoria');
        $pagina = max(1, (int) $request->query('pagina', 1));

        // Una letra suelta no busca nada útil; sin texto ni categoría se listan los modelos con stock, de 30 en 30.
        if ($q !== '' && mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $productos = Product::with([
                'brand',
                'category',
                'variants' => fn ($v) => $v->where('stock', '>', 0)->with('productColor'),
            ])
            ->whereHas('variants', fn ($v) => $v->where('stock', '>', 0))
            ->when($q, fn ($pq) => $pq->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhereHas('brand', fn ($bq) => $bq->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('colors', fn ($cq) => $cq->where('name', 'like', "%{$q}%"));
            }))
            ->when($categoria, fn ($pq) => $pq->whereHas('category', fn ($cq) => $cq->where('name', $categoria)))
            ->orderBy('name')
            ->orderBy('id')
            ->skip(($pagina - 1) * 30)
            ->limit(30)
            ->get()
            ->map(fn ($p) => [
                'productId' => $p->id,
                'producto' => $p->name,
                'marca' => $p->brand->name ?? '-',
                'categoria' => $p->category->name ?? '-',
                'variantes' => $p->variants->map(fn ($v) => [
                    'variantId' => $v->id,
                    'talla' => $v->size,
                    'color' => $v->productColor->name ?? '-',
                    'colorHex' => $v->productColor->hex ?? null,
                    'stock' => (int) $v->stock,
                    'precioVenta' => (float) ($v->price ?? $p->price),
                    'costo' => (float) $v->cost,
                ])->values(),
            ]);

        return response()->json($productos);
    }

    // Lectura de un código de barras (pistola o cámara): devuelve la variante exacta con la forma que usa el ticket.
    public function buscarCodigo(Request $request)
    {
        $codigo = ProductVariant::normalizarCodigo((string) $request->query('codigo', ''));

        $variant = $codigo === '' ? null : ProductVariant::with(['product.brand', 'productColor'])->where('barcode', $codigo)->first();

        if (! $variant) {
            return response()->json(['message' => "No hay ninguna prenda con el código {$codigo}."], 404);
        }

        $nombre = ($variant->product->name ?? '') . ' · ' . ($variant->productColor->name ?? 'Sin color') . ' · ' . $variant->size;

        if ($variant->stock <= 0) {
            return response()->json(['message' => "{$nombre} no tiene stock."], 422);
        }

        return response()->json([
            'variantId' => $variant->id,
            'producto' => $variant->product->name,
            'marca' => $variant->product->brand->name ?? '-',
            'talla' => $variant->size,
            'color' => $variant->productColor->name ?? '-',
            'colorHex' => $variant->productColor->hex ?? null,
            'stock' => (int) $variant->stock,
            'precioVenta' => (float) ($variant->price ?? $variant->product->price),
            'costo' => (float) $variant->cost,
        ]);
    }

    // Busca entre los clientes con cuenta registrada, para poder vincular la venta a su
    // historial real. Si no encuentra a nadie, el vendedor igual puede escribir el nombre
    // a mano (venta sin cliente vinculado, para compradores de paso sin cuenta).
    public function searchClientes(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $clientes = Customer::where('name', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->orWhere('phone', 'like', "%{$q}%")
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json($clientes);
    }

    // Al escribir un DNI/RUC en el ticket: primero se mira si ya es cliente; si no, se consulta
    // RENIEC/SUNAT solo para traer el nombre. NO se crea nada aquí: el cliente nuevo se registra
    // al guardar la venta (así cancelar el ticket no deja clientes sueltos).
    public function buscarDocumento(Request $request, ApiPeruService $apiPeru)
    {
        $request->validate([
            'tipo' => 'required|in:DNI,RUC',
            'numero' => 'required|digits_between:8,11',
        ]);

        if (! $this->documentoValido($request->tipo, $request->numero)) {
            return response()->json(['message' => 'El número no corresponde al tipo de documento.'], 422);
        }

        $existente = Customer::where('document_number', $request->numero)->first(['id', 'name', 'email', 'phone', 'document_type', 'document_number']);
        if ($existente) {
            return response()->json(['existe' => true, 'cliente' => $existente]);
        }

        try {
            $datos = $request->tipo === 'DNI' ? $apiPeru->dni($request->numero) : $apiPeru->ruc($request->numero);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'No se pudo consultar el documento. Escribe el nombre a mano.'], 422);
        }

        return response()->json(['existe' => false, 'nombre' => $datos['nombre']]);
    }

    private function documentoValido(?string $tipo, ?string $numero): bool
    {
        return match ($tipo) {
            'DNI' => preg_match('/^\d{8}$/', (string) $numero) === 1,
            'RUC' => preg_match('/^(10|20)\d{9}$/', (string) $numero) === 1,
            default => false,
        };
    }

    public function store(Request $request, InventoryService $inventario)
    {
        $validated = $request->validate([
            'fecha' => 'required|date',
            'cliente' => 'nullable|string|max:255',
            'clienteId' => 'nullable|integer|exists:customers,id',
            'documentoTipo' => 'nullable|in:DNI,RUC|required_with:documentoNumero',
            'documentoNumero' => [
                'nullable',
                'string',
                fn ($attr, $value, $fail) => $this->documentoValido($request->input('documentoTipo'), $value) ?: $fail('El número de documento no es válido.'),
            ],
            'notas' => 'nullable|string',
            'prendas' => 'required|array|min:1',
            'prendas.*.variantId' => 'required|integer|exists:product_variants,id',
            'prendas.*.cantidad' => 'required|integer|min:1',
            'prendas.*.precioVenta' => 'required|numeric|min:0',
        ]);

        // Cuántas unidades de cada variante se necesitan en total (sumando líneas
        // repetidas de la misma prenda en el ticket).
        $cantidadesPorVariante = collect($validated['prendas'])
            ->groupBy('variantId')
            ->map(fn ($grupo) => $grupo->sum('cantidad'));

        try {
            $sale = DB::transaction(function () use ($validated, $cantidadesPorVariante, $inventario) {
                // Bloquea las variantes involucradas (en orden fijo por id, para que dos
                // ventas simultáneas con las mismas prendas no se hagan deadlock entre sí)
                // y recién con el lock tomado valida el stock — así dos ventas al mismo
                // tiempo no pueden pasar ambas la validación antes de que la otra descuente.
                $variantesBloqueadas = ProductVariant::whereIn('id', $cantidadesPorVariante->keys())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($cantidadesPorVariante as $variantId => $cantidadTotal) {
                    $variant = $variantesBloqueadas->get($variantId);
                    if (!$variant || $variant->stock < $cantidadTotal) {
                        throw new \RuntimeException('No hay stock suficiente para completar la venta.');
                    }
                }

                $total = collect($validated['prendas'])->sum(fn ($p) => $p['cantidad'] * $p['precioVenta']);

                $clienteVinculado = null;

                if (!empty($validated['clienteId'])) {
                    $clienteVinculado = Customer::find($validated['clienteId']);
                } elseif (!empty($validated['documentoNumero'])) {
                    // Cliente identificado por documento: si ya está registrado se reutiliza;
                    // si no, se crea ahora (recién al guardar la venta) con el nombre del ticket.
                    $clienteVinculado = Customer::where('document_number', $validated['documentoNumero'])->first();

                    if (!$clienteVinculado) {
                        $nombre = trim((string) ($validated['cliente'] ?? ''));
                        if ($nombre === '') {
                            throw new \RuntimeException('Escribe el nombre del cliente para registrarlo con ese documento.');
                        }

                        $clienteVinculado = Customer::create([
                            'name' => $nombre,
                            'document_type' => $validated['documentoTipo'],
                            'document_number' => $validated['documentoNumero'],
                            'password' => Hash::make(Str::random(40)),
                        ]);
                    }
                } elseif (!empty($validated['cliente'])) {
                    $nombreCliente = trim($validated['cliente']);

                    // No eligieron un cliente del buscador, pero escribieron un nombre a
                    // mano: se reutiliza uno ya registrado con ese mismo nombre (evita
                    // duplicados) o, si no existe, se crea como "solo registro" — igual
                    // que si lo hubieran dado de alta desde Admin > Clientes. Así toda
                    // venta con nombre queda vinculada a un cliente real de verdad.
                    $homonimos = Customer::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($nombreCliente)])->get();

                    if ($homonimos->count() === 1 && !$homonimos->first()->document_number) {
                        // Un registro antiguo solo con nombre: se reutiliza.
                        $clienteVinculado = $homonimos->first();
                    } elseif ($homonimos->isNotEmpty()) {
                        // Ya hay alguien con ese nombre (y con documento, o varios): podría ser otra persona, así que no se
                        // adivina — se pide elegirlo de la lista o distinguirlo con su DNI.
                        throw new \RuntimeException('Ya hay ' . ($homonimos->count() === 1 ? 'un cliente llamado' : "{$homonimos->count()} clientes llamados") . " «{$nombreCliente}». Elígelo de la lista al escribir su nombre, o escribe el DNI para distinguirlo.");
                    } else {
                        $clienteVinculado = Customer::create([
                            'name' => $nombreCliente,
                            'password' => Hash::make(Str::random(40)),
                        ]);
                    }
                }

                $sale = Sale::create([
                    'user_id' => Auth::guard('web')->id(),
                    'sale_date' => $validated['fecha'],
                    'customer_name' => $clienteVinculado->name ?? null,
                    'customer_id' => $clienteVinculado->id ?? null,
                    'notes' => $validated['notas'] ?? null,
                    'total' => $total,
                ]);

                foreach ($validated['prendas'] as $item) {
                    $variant = $variantesBloqueadas->get($item['variantId']);
                    $cantidad = $item['cantidad'];

                    $inventario->sell($sale, $variant, $cantidad, (float) $item['precioVenta']);
                }

                return $sale;
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('admin.sales.create')
            ->with('success', 'Venta registrada correctamente.')
            ->with('ventaId', $sale->id);
    }

    // Revierte una venta completa: devuelve el stock a las variantes y a los lotes de
    // compra de donde salió, y la marca como cancelada (no se borra, queda en el historial).
    // Solo un administrador puede hacerlo, ya que afecta cifras de ingresos ya reportadas.
    public function cancel(Sale $venta)
    {
        if (Auth::guard('web')->user()->role !== 'admin') {
            abort(403, 'Solo un administrador puede cancelar una venta.');
        }

        if ($venta->cancelled_at !== null) {
            return back()->withErrors(['error' => 'Esta venta ya estaba cancelada.']);
        }

        DB::transaction(function () use ($venta) {
            $venta->load('items.batches');

            foreach ($venta->items as $item) {
                $variant = ProductVariant::find($item->product_variant_id);

                if ($variant) {
                    $variant->increment('stock', $item->quantity);
                    $variant->product->increment('stock', $item->quantity);
                }

                foreach ($item->batches as $batch) {
                    PurchaseItem::where('id', $batch->purchase_item_id)
                        ->increment('quantity_remaining', $batch->quantity);
                }
            }

            $venta->update([
                'cancelled_at' => now(),
                'cancelled_by' => Auth::guard('web')->id(),
            ]);
        });

        return back()->with('success', 'Venta cancelada. El stock fue devuelto.');
    }

    // Devuelve solo algunas unidades de una línea de venta (a diferencia de cancel(), que
    // anula la venta completa). Igual que cancel(), solo un administrador puede hacerlo,
    // y el stock/lotes se revierten exactamente igual, pero por la cantidad devuelta.
    public function returnItem(Request $request, SaleItem $item)
    {
        if (Auth::guard('web')->user()->role !== 'admin') {
            abort(403, 'Solo un administrador puede registrar una devolución.');
        }

        $item->load('sale', 'batches');

        if ($item->sale->cancelled_at !== null) {
            return back()->withErrors(['error' => 'Esta venta ya está cancelada, no hay nada que devolver.']);
        }

        $pendiente = $item->net_quantity;

        $validated = $request->validate([
            'cantidad' => "required|integer|min:1|max:{$pendiente}",
            'motivo' => 'nullable|string|max:500',
        ]);

        $cantidad = $validated['cantidad'];

        DB::transaction(function () use ($item, $cantidad, $validated) {
            $variant = ProductVariant::find($item->product_variant_id);

            if ($variant) {
                $variant->increment('stock', $cantidad);
                $variant->product->increment('stock', $cantidad);
            }

            // Se devuelve a los lotes en orden inverso al que se consumieron (el último lote
            // tomado es el primero en recibir la devolución), y se reduce lo consumido de cada
            // lote — así, si más adelante se cancela la venta completa, solo queda por revertir
            // lo que de verdad sigue "vendido".
            $porDevolver = $cantidad;

            foreach ($item->batches->sortByDesc('id') as $batch) {
                if ($porDevolver <= 0) {
                    break;
                }

                $devolverDeEsteLote = min($porDevolver, $batch->quantity);

                PurchaseItem::where('id', $batch->purchase_item_id)
                    ->increment('quantity_remaining', $devolverDeEsteLote);

                if ($devolverDeEsteLote >= $batch->quantity) {
                    $batch->delete();
                } else {
                    $batch->decrement('quantity', $devolverDeEsteLote);
                }

                $porDevolver -= $devolverDeEsteLote;
            }

            $item->increment('returned_quantity', $cantidad);

            SaleItemReturn::create([
                'sale_item_id' => $item->id,
                'quantity' => $cantidad,
                'reason' => $validated['motivo'] ?? null,
                'returned_by' => Auth::guard('web')->id(),
            ]);
        });

        return back()->with('success', 'Devolución registrada. El stock fue actualizado.');
    }

    private function claveCliente(Sale $sale): string
    {
        if ($sale->customer_id) {
            return "id:{$sale->customer_id}";
        }

        return 'nombre:' . ($sale->customer_name ?: 'Sin cliente registrado');
    }
}
