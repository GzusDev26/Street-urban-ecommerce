<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Avatar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

// Autoservicio del usuario logueado (admin o vendedor) para editar su propio nombre,
// email, contraseña y avatar. La baja/alta de cuentas de staff y el cambio de rol se
// hacen desde Admin > Usuarios (Admin\UserAdminController), que sí tiene los resguardos
// de "no te quedes sin administradores" — por eso esta página no ofrece eliminar la cuenta.
class ProfileController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Profile/Edit', [
            'avatares' => Avatar::latest()->get()->map(fn ($a) => [
                'id' => $a->id,
                'url' => $a->url,
            ]),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('success', 'Perfil actualizado correctamente.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'avatar_id' => 'nullable|exists:avatars,id',
        ]);

        $request->user()->update(['avatar_id' => $validated['avatar_id'] ?? null]);

        return Redirect::route('profile.edit')->with('success', 'Avatar actualizado.');
    }
}
