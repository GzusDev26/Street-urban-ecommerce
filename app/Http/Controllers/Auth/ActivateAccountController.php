<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\VerificationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ActivateAccountController extends Controller
{
    private const PAGE = 'Auth/ActivateAccount';

    public function show(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');
        $skipVerification = $request->query('skip-verification');

        // Si viene con skip-verification, es un email que ya fue verificado
        if ($skipVerification && $email) {
            return Inertia::render(self::PAGE, [
                'token' => null,
                'email' => $email,
                'isValid' => true,
                'skipVerification' => true,
            ]);
        }

        if (!$token || !$email) {
            return Inertia::render(self::PAGE, [
                'error' => 'Token o email inválido',
                'isValid' => false,
            ]);
        }

        // Verificar que el token sea válido
        $isValid = VerificationToken::isValidToken($email, $token);

        if (!$isValid) {
            return Inertia::render(self::PAGE, [
                'error' => 'Token expirado o inválido',
                'isValid' => false,
            ]);
        }

        return Inertia::render(self::PAGE, [
            'token' => $token,
            'email' => $email,
            'isValid' => true,
            'skipVerification' => false,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'token' => 'nullable|string',
            'email' => 'required|email',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'skipVerification' => 'nullable|boolean',
        ]);

        try {
            // Verificar token válido (solo si no es skip-verification)
            if (!$validated['skipVerification'] && !VerificationToken::isValidToken($validated['email'], $validated['token'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido o expirado',
                ], 400);
            }

            // Un email de staff no puede registrarse como cliente
            if (\App\Models\User::where('email', $validated['email'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El email ya está registrado',
                ], 400);
            }

            // Verificar si el cliente ya existe
            $customer = Customer::where('email', $validated['email'])->first();

            if ($customer && !$validated['skipVerification']) {
                // Cliente existe con contraseña en registro normal
                return response()->json([
                    'success' => false,
                    'message' => 'El email ya está registrado',
                ], 400);
            }

            if ($customer && $validated['skipVerification']) {
                // Activa un cliente que ya existía sin contraseña real (ej. registrado
                // desde el admin durante una venta en tienda) — ahora sí puede loguearse.
                $customer->update([
                    'password' => Hash::make($validated['password']),
                    'email_verified_at' => now(),
                ]);
            } elseif (!$customer) {
                // Crear nuevo cliente solo si no existe
                $customer = Customer::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'password' => Hash::make($validated['password']),
                    'email_verified_at' => now(),
                ]);
            }

            // Marcar token como verificado (solo si no es skip-verification)
            if (!$validated['skipVerification']) {
                VerificationToken::markAsVerified($validated['email'], $validated['token']);
            }

            // Auto-login del cliente usando el guard de customer
            Auth::guard('customer')->login($customer);

            return response()->json([
                'success' => true,
                'message' => 'Cuenta activada correctamente',
                'redirect' => '/',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la cuenta: ' . $e->getMessage(),
            ], 500);
        }
    }
}
