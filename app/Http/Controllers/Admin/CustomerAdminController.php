<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Services\ApiPeruService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class CustomerAdminController extends Controller
{
    public function index()
    {
        $customers = Customer::orderByDesc('created_at')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'document_type' => $c->document_type,
                'document_number' => $c->document_number,
                'email' => $c->email,
                'phone' => $c->phone,
                'activa' => $c->email_verified_at !== null,
                'registrado' => $c->created_at->format('d/m/Y'),
            ]);

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
        ]);
    }

    // El alta ahora se hace desde un modal en el índice; esta ruta se deja por si algo
    // la visita directamente (la genera Route::resource), pero no tiene vista propia.
    public function create()
    {
        return redirect()->route('admin.customers.index');
    }

    // Un cliente registrado desde el admin (ej. durante una venta en tienda física) no
    // necesita ni tiene por qué tener acceso a la web — solo se guarda su nombre/contacto
    // para historial. Se crea con una contraseña aleatoria e inutilizable; si más adelante
    // quiere comprar online, activa su cuenta él mismo con su email (flujo de activate-account),
    // que ya sabe reconocer a un cliente existente sin contraseña real.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document_type' => 'nullable|in:DNI,RUC|required_with:document_number',
            'document_number' => [
                'nullable',
                'digits_between:8,11',
                'unique:customers,document_number',
                fn ($attr, $value, $fail) => $this->documentoValido($request->input('document_type'), $value) ?: $fail('El número no corresponde al tipo de documento.'),
            ],
            'email' => [
                'nullable',
                'email',
                'unique:customers,email',
                Rule::notIn(User::pluck('email')->all()),
            ],
            'phone' => 'nullable|string|max:20',
        ], [
            'email.not_in' => 'Ese correo ya está en uso por una cuenta de staff.',
        ]);

        Customer::create([
            'name' => $validated['name'],
            'document_type' => $validated['document_number'] ?? null ? $validated['document_type'] : null,
            'document_number' => $validated['document_number'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make(Str::random(40)),
        ]);

        return redirect()->route('admin.customers.index')->with('success', 'Cliente registrado exitosamente.');
    }

    // Autocompleta nombre (y dirección, en RUC) desde RENIEC/SUNAT vía APIPERU.
    public function consultarDocumento(Request $request, ApiPeruService $apiPeru)
    {
        $request->validate([
            'tipo' => 'required|in:DNI,RUC',
            'numero' => 'required|digits_between:8,11',
            'excluir' => 'nullable|integer',
        ]);

        if (! $this->documentoValido($request->tipo, $request->numero)) {
            return response()->json(['message' => 'El número no corresponde al tipo de documento.'], 422);
        }

        $existente = Customer::where('document_number', $request->numero)
            ->when($request->excluir, fn ($q, $id) => $q->where('id', '!=', $id))
            ->first();
        if ($existente) {
            return response()->json(['message' => "Ya existe un cliente con ese documento: {$existente->name}."], 409);
        }

        try {
            $datos = $request->tipo === 'DNI'
                ? $apiPeru->dni($request->numero)
                : $apiPeru->ruc($request->numero);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'No se pudo consultar el documento. Ingresa los datos manualmente.'], 422);
        }

        return response()->json($datos);
    }

    private function documentoValido(?string $tipo, ?string $numero): bool
    {
        if ($numero === null || $numero === '') {
            return true;
        }

        return match ($tipo) {
            'DNI' => preg_match('/^\d{8}$/', $numero) === 1,
            'RUC' => preg_match('/^(10|20)\d{9}$/', $numero) === 1,
            default => false,
        };
    }

    // La edición se hace desde un modal en el índice; esta ruta se deja por si algo la
    // visita directamente (la genera Route::resource), pero no tiene vista propia.
    public function edit()
    {
        return redirect()->route('admin.customers.index');
    }

    // Historial de compras de un cliente, para el botón aparte en la tabla de Clientes
    // (no es parte del formulario de edición — son dos acciones distintas).
    public function historial(Customer $customer)
    {
        $ventas = $customer->sales()
            ->with('items.variant.product', 'items.variant.productColor')
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->get()
            ->map(function ($v) {
                $nombres = $v->items->pluck('variant.product.name')->unique()->values();
                $producto = $nombres->count() <= 1
                    ? ($nombres->first() ?? 'Sin prendas')
                    : "{$nombres->first()} + " . ($nombres->count() - 1) . ' más';

                // Una venta puede incluir varios colores del mismo modelo — se muestran
                // hasta 3 y se resume el resto, en vez de aparentar que solo hubo un color.
                $coloresUnicos = $v->items->map(fn ($i) => $i->variant->productColor)->filter()->unique('id')->values();

                return [
                    'id' => $v->id,
                    'numero' => 'V-' . str_pad($v->id, 5, '0', STR_PAD_LEFT),
                    'fecha' => $v->sale_date->format('d/m/Y'),
                    'producto' => $producto,
                    'colores' => $coloresUnicos->take(3)->pluck('hex')->values(),
                    'masColores' => max(0, $coloresUnicos->count() - 3),
                    'total' => (float) $v->total,
                    'cancelada' => $v->cancelled_at !== null,
                ];
            });

        $vigentes = $ventas->reject('cancelada');

        return response()->json([
            'ventas' => $ventas,
            'stats' => [
                'totalCompras' => $vigentes->count(),
                'totalGastado' => $vigentes->sum('total'),
            ],
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document_type' => 'nullable|in:DNI,RUC|required_with:document_number',
            'document_number' => [
                'nullable',
                'digits_between:8,11',
                Rule::unique('customers', 'document_number')->ignore($customer->id),
                fn ($attr, $value, $fail) => $this->documentoValido($request->input('document_type'), $value) ?: $fail('El número no corresponde al tipo de documento.'),
            ],
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:20',
        ]);

        if (empty($validated['document_number'])) {
            $validated['document_type'] = null;
            $validated['document_number'] = null;
        }

        $customer->update($validated);

        return redirect()->route('admin.customers.index')->with('success', 'Cliente actualizado exitosamente');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('admin.customers.index')->with('success', 'Cliente eliminado exitosamente');
    }
}
