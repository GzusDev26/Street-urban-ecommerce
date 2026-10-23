<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    // Mismo criterio que LoginRequest: 5 intentos fallidos por correo + IP, luego bloqueo de 1 minuto.
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 60;

    public function loginAction(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if ($seconds = $this->secondsUntilUnlocked($request)) {
            return $this->loginFailed($request, $this->lockoutMessage($seconds), 429);
        }

        $user = User::where('email', $email)->first();
        if ($user && Hash::check($password, $user->password)) {
            // Solo se dice que está desactivada a quien ya demostró saber la contraseña.
            if (! $user->active) {
                return $this->loginFailed($request, 'Tu cuenta está desactivada. Contacta al administrador.');
            }

            RateLimiter::clear($this->throttleKey($request));
            Auth::guard('web')->login($user, true);
            return $this->loginSucceeded($request, '/admin/dashboard');
        }

        $customer = Customer::where('email', $email)->first();
        if ($customer && Hash::check($password, $customer->password)) {
            RateLimiter::clear($this->throttleKey($request));
            Auth::guard('customer')->login($customer, true);
            return $this->loginSucceeded($request, '/');
        }

        // Mismo mensaje si el correo no existe o si la contraseña falla, para no revelar qué cuentas existen.
        return $this->loginFailed($request, $this->registerFailedAttempt($request));
    }

    // El modal de la tienda llama por fetch y espera JSON; un POST de formulario normal sigue recibiendo redirect.
    private function loginSucceeded(Request $request, string $redirect)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => $redirect]);
        }

        return redirect($redirect);
    }

    private function loginFailed(Request $request, string $message, int $status = 422)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        return redirect('/')->with('error', $message);
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }

    // Devuelve los segundos que faltan para desbloquear, o 0 si todavía puede intentar.
    private function secondsUntilUnlocked(Request $request): int
    {
        $key = $this->throttleKey($request);

        return RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS) ? RateLimiter::availableIn($key) : 0;
    }

    // Suma el intento fallido y devuelve el mensaje genérico con los intentos que le quedan.
    private function registerFailedAttempt(Request $request): string
    {
        $key = $this->throttleKey($request);
        RateLimiter::hit($key, self::DECAY_SECONDS);

        $remaining = RateLimiter::remaining($key, self::MAX_ATTEMPTS);

        if ($remaining === 0) {
            return 'El correo o la contraseña son incorrectos. '.$this->lockoutMessage(RateLimiter::availableIn($key));
        }

        return 'El correo o la contraseña son incorrectos. '
            .($remaining === 1 ? 'Te queda 1 intento.' : "Te quedan {$remaining} intentos.");
    }

    private function lockoutMessage(int $seconds): string
    {
        return "Has superado el número de intentos. Intenta de nuevo en {$seconds} segundos.";
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if ($seconds = $this->secondsUntilUnlocked($request)) {
            return response()->json([
                'success' => false,
                'message' => $this->lockoutMessage($seconds),
            ], 429);
        }

        $customer = Customer::where('email', $validated['email'])->first();

        if (!$customer || !Hash::check($validated['password'], $customer->password)) {
            return response()->json([
                'success' => false,
                'message' => $this->registerFailedAttempt($request),
            ], 401);
        }

        // Login del customer
        RateLimiter::clear($this->throttleKey($request));
        Auth::guard('customer')->login($customer);

        return response()->json([
            'success' => true,
            'message' => 'Sesión iniciada correctamente',
            'redirect' => '/',
        ]);
    }
}
