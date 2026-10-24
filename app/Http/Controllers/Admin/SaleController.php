<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
use Illuminate\Http\Request;
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

    public function index()
    {
        $ventas = Sale::with(['items.variant.product.brand', 'items.variant.productColor', 'user', 'cancelledBy'])
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($v) => $this->mapVenta($v));

        // Las ventas canceladas se siguen mostrando en el historial (por trazabilidad),
        // pero no cuentan como ingresos ni prendas realmente vendidas. Se usa el total y
        // las prendas "neto" (restando devoluciones parciales) para que las cifras reflejen
        // lo que de verdad se quedó vendido.
        $vigentes = $ventas->reject('cancelada');

        return Inertia::render('Admin/Ventas/Index', [
            'ventas' => $ventas,
            'stats' => [
                'totalVentas' => $vigentes->count(),
                'totalIngresos' => $vigentes->sum('totalNeto'),
                'prendasVendidas' => $vigentes->sum(fn ($v) => $v['totalPrendas'] - $v['prendasDevueltas']),
                'promedioVenta' => $vigentes->count() > 0 ? $vigentes->sum('totalNeto') / $vigentes->count() : 0,
            ],
        ]);
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
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');
        $cliente = $request->query('cliente');
        $categoria = $request->query('categoria');

        // Las ventas canceladas no fueron ingresos reales, así que no entran al reporte.
        $itemsQuery = SaleItem::with(['sale', 'variant.product.category', 'variant.product.brand'])
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

        $items = $itemsQuery->get();

        // Todo el reporte trabaja en "neto" (cantidad y subtotal ya restando lo devuelto),
        // para que ingresos/utilidad no incluyan prendas que el cliente regresó.
        $numeroVentas = $items->pluck('sale_id')->unique()->count();
        $totalIngresos = $items->sum('net_subtotal');
        $utilidadTotal = $items->sum(fn ($i) => ($i->unit_price - $i->unit_cost) * $i->net_quantity);

        $porCategoria = $items->groupBy(fn ($i) => $i->variant->product->category->name ?? 'Sin categoría')
            ->map(fn ($group, $nombre) => [
                'label' => $nombre,
                'total' => $group->sum('net_subtotal'),
                'unidades' => $group->sum('net_quantity'),
                'ventas' => $group->pluck('sale_id')->unique()->count(),
            ])
            ->sortByDesc('total')->values();

        // Se agrupa por customer_id cuando la venta está vinculada a un cliente real, para
        // no confundir a dos personas distintas que escribieron el mismo nombre a mano.
        $porCliente = $items->groupBy(fn ($i) => $this->claveCliente($i->sale))
            ->map(fn ($group) => [
                'label' => $group->first()->sale->customer_name ?: 'Sin cliente registrado',
                'total' => $group->sum('net_subtotal'),
                'unidades' => $group->sum('net_quantity'),
                'ventas' => $group->pluck('sale_id')->unique()->count(),
            ])
            ->sortByDesc('total')->values();

        $porMarca = $items->groupBy(fn ($i) => $i->variant->product->brand->name ?? 'Sin marca')
            ->map(fn ($group, $nombre) => [
                'label' => $nombre,
                'total' => $group->sum('net_subtotal'),
                'unidades' => $group->sum('net_quantity'),
                'ventas' => $group->pluck('sale_id')->unique()->count(),
            ])
            ->sortByDesc('total')->values();

        $topProductos = $items->groupBy(fn ($i) => $i->variant->product->name)
            ->map(function ($group, $nombre) {
                $unidades = $group->sum('net_quantity');
                $total = $group->sum('net_subtotal');

                return [
                    'label' => $nombre,
                    'marca' => $group->first()->variant->product->brand->name ?? '-',
                    'categoria' => $group->first()->variant->product->category->name ?? 'Sin categoría',
                    'unidades' => $unidades,
                    'numeroVentas' => $group->pluck('sale_id')->unique()->count(),
                    'precioPromedio' => $unidades > 0 ? $total / $unidades : 0,
                    'total' => $total,
                    'utilidad' => $group->sum(fn ($i) => ($i->unit_price - $i->unit_cost) * $i->net_quantity),
                ];
            })
            ->sortByDesc('unidades')->values()->take(10);

        return Inertia::render('Admin/Ventas/Reportes', [
            'filtros' => compact('desde', 'hasta', 'cliente', 'categoria'),
            'clientes' => Sale::whereNotNull('customer_name')->where('customer_name', '!=', '')->distinct()->orderBy('customer_name')->pluck('customer_name'),
            'categorias' => Category::orderBy('name')->pluck('name'),
            'stats' => [
                'totalIngresos' => $totalIngresos,
                'numeroVentas' => $numeroVentas,
                'totalUnidades' => $items->sum('net_quantity'),
                'promedioPorVenta' => $numeroVentas > 0 ? $totalIngresos / $numeroVentas : 0,
                'utilidadTotal' => $utilidadTotal,
            ],
            'porCategoria' => $porCategoria,
            'porCliente' => $porCliente,
            'porMarca' => $porMarca,
            'topProductos' => $topProductos,
        ]);
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

        if (mb_strlen($q) < 2 && !$categoria) {
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
                ])->values(),
            ]);

        return response()->json($productos);
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
                    $clienteVinculado = Customer::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($nombreCliente)])->first();

                    if (!$clienteVinculado) {
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
