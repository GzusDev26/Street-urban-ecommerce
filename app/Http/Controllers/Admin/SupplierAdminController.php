<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierAdminController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::orderByDesc('created_at')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'phone' => $s->phone,
                'email' => $s->email,
                'address' => $s->address,
                'registrado' => $s->created_at->format('d/m/Y'),
            ]);

        return Inertia::render('Admin/Suppliers/Index', [
            'suppliers' => $suppliers,
        ]);
    }

    // El alta se hace desde un modal en el índice; esta ruta se deja por si algo la
    // visita directamente (la genera Route::resource), pero no tiene vista propia.
    public function create()
    {
        return redirect()->route('admin.suppliers.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        Supplier::create($validated);

        return redirect()->route('admin.suppliers.index')->with('success', 'Proveedor registrado exitosamente.');
    }

    // La edición se hace desde un modal en el índice; esta ruta se deja por si algo la
    // visita directamente (la genera Route::resource), pero no tiene vista propia.
    public function edit()
    {
        return redirect()->route('admin.suppliers.index');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $supplier->update($validated);

        return redirect()->route('admin.suppliers.index')->with('success', 'Proveedor actualizado exitosamente.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('admin.suppliers.index')->with('success', 'Proveedor eliminado exitosamente.');
    }

    // Historial de compras hechas a este proveedor, para el botón aparte en la tabla
    // de Proveedores (misma idea que el historial de compras de un cliente).
    public function historial(Supplier $supplier)
    {
        $compras = $supplier->purchases()
            ->with('items.variant.product', 'items.variant.productColor')
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->get()
            ->map(function ($p) {
                $nombres = $p->items->pluck('variant.product.name')->unique()->values();
                $producto = $nombres->count() <= 1
                    ? ($nombres->first() ?? 'Sin prendas')
                    : "{$nombres->first()} + " . ($nombres->count() - 1) . ' más';

                // Una compra suele traer varios colores del mismo modelo (ej. blanco,
                // negro, rojo en un solo pedido) — se muestran hasta 3 y se resume el resto,
                // en vez de aparentar que solo hubo un color.
                $coloresUnicos = $p->items->map(fn ($i) => $i->variant->productColor)->filter()->unique('id')->values();

                return [
                    'id' => $p->id,
                    'numero' => 'C-' . str_pad($p->id, 5, '0', STR_PAD_LEFT),
                    'fecha' => $p->purchase_date->format('d/m/Y'),
                    'producto' => $producto,
                    'colores' => $coloresUnicos->take(3)->pluck('hex')->values(),
                    'masColores' => max(0, $coloresUnicos->count() - 3),
                    'total' => (float) $p->total,
                    'cancelada' => $p->cancelled_at !== null,
                ];
            });

        $vigentes = $compras->reject('cancelada');

        return response()->json([
            'compras' => $compras,
            'stats' => [
                'totalCompras' => $vigentes->count(),
                'totalInvertido' => $vigentes->sum('total'),
            ],
        ]);
    }
}
