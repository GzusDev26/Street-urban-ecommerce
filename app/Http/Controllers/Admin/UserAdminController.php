<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

// Toda esta sección ya está protegida por el middleware admin.only (ver routes/web.php) —
// solo un administrador puede llegar a estas acciones.
class UserAdminController extends Controller
{
    public function index()
    {
        $users = User::with('avatar')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'avatarUrl' => $u->avatar_url,
                'esYo' => $u->id === Auth::id(),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    // El alta se hace desde un modal en el índice; esta ruta se deja por si algo la
    // visita directamente (la genera Route::resource), pero no tiene vista propia.
    public function create()
    {
        return redirect()->route('admin.users.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'unique:users,email', 'unique:customers,email'],
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in(['admin', 'vendedor'])],
        ], [
            'email.unique' => 'Ese correo ya está en uso.',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Usuario registrado exitosamente.');
    }

    // La edición se hace desde un modal en el índice; esta ruta se deja por si algo la
    // visita directamente (la genera Route::resource), pero no tiene vista propia.
    public function edit()
    {
        return redirect()->route('admin.users.index');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'unique:users,email,' . $user->id, 'unique:customers,email'],
            'role' => ['required', Rule::in(['admin', 'vendedor'])],
            'password' => 'nullable|string|min:6',
        ], [
            'email.unique' => 'Ese correo ya está en uso.',
        ]);

        // No te puedes quitar el rol de admin a ti mismo si eres el único que queda —
        // te dejaría el sistema sin nadie que pueda gestionar usuarios/cancelaciones.
        if ($user->id === Auth::id() && $user->role === 'admin' && $validated['role'] !== 'admin') {
            $quedanOtrosAdmins = User::where('role', 'admin')->where('id', '!=', $user->id)->exists();
            if (!$quedanOtrosAdmins) {
                return back()->withErrors(['role' => 'No puedes quitarte el rol de admin: eres el único administrador.']);
            }
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            ...(!empty($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'No puedes eliminar tu propia cuenta.']);
        }

        if ($user->role === 'admin') {
            $quedanOtrosAdmins = User::where('role', 'admin')->where('id', '!=', $user->id)->exists();
            if (!$quedanOtrosAdmins) {
                return back()->withErrors(['error' => 'No puedes eliminar al único administrador.']);
            }
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado exitosamente.');
    }
}
