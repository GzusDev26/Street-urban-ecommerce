<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Si desactivan a un usuario con la sesión abierta, en su siguiente petición se le cierra la
// sesión del panel (no se invalida la sesión completa: la del cliente de la tienda es aparte).
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user && ! $user->active) {
            Auth::guard('web')->logout();

            return redirect('/')->with('error', 'Tu cuenta fue desactivada. Contacta al administrador.');
        }

        return $next($request);
    }
}
