<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function loginAction(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::where('email', $email)->first();
        if ($user && Hash::check($password, $user->password)) {
            Auth::guard('web')->login($user, true);
            return redirect('/admin/dashboard');
        }

        $customer = Customer::where('email', $email)->first();
        if ($customer && Hash::check($password, $customer->password)) {
            Auth::guard('customer')->login($customer, true);
            return redirect('/');
        }

        return redirect('/')->with('error', 'Credenciales inválidas');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        $customer = Customer::where('email', $validated['email'])->first();

        if (!$customer || !Hash::check($validated['password'], $customer->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email o contraseña incorrectos',
            ], 401);
        }

        // Login del customer
        Auth::guard('customer')->login($customer);

        return response()->json([
            'success' => true,
            'message' => 'Sesión iniciada correctamente',
            'redirect' => '/',
        ]);
    }
}
