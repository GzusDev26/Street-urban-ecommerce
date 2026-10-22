<?php

namespace App\Http\Middleware;

use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $staff = Auth::guard('web')->user();
        $customer = Auth::guard('customer')->user();

        $type = null;
        if ($staff) {
            $type = 'staff';
        } elseif ($customer) {
            $type = 'customer';
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $staff ?? $customer,
                'type' => $type,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'complaint_number' => $request->session()->get('complaint_number'),
                'ventaId' => $request->session()->get('ventaId'),
            ],
            // Compartido globalmente para que cualquier página (tienda o admin) pueda
            // usar el nombre/contacto del negocio sin que cada controller lo pase a mano.
            'business' => fn () => BusinessSetting::current()->only([
                'name', 'email', 'phone', 'whatsapp', 'address', 'ruc', 'low_stock_threshold',
            ]),
        ];
    }
}
