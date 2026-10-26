<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Avatar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

// Protegido por el middleware admin.only (ver routes/web.php). Un admin sube aquí la
// galería de avatares; cada usuario de staff elige el suyo desde su propio Perfil.
class AvatarAdminController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Avatares/Index', [
            'avatares' => Avatar::withCount('users')->latest()->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'url' => $a->url,
                    'enUso' => $a->users_count,
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'imagen' => 'required|image|max:2048',
        ]);

        Avatar::create([
            'path' => $request->file('imagen')->store('avatars', 'public'),
        ]);

        return back()->with('success', 'Avatar agregado correctamente.');
    }

    public function destroy(Avatar $avatar)
    {
        if (! $avatar->esDelSitio()) {
            Storage::disk('public')->delete($avatar->path);
        }
        $avatar->delete();

        return back()->with('success', 'Avatar eliminado. Quienes lo tenían elegido vuelven a sus iniciales.');
    }
}
