<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ProductColor;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Support\PublicFile;

class WishlistController extends Controller
{
    // La wishlist la puede usar cualquier cuenta logueada, sea staff (guard "web")
    // o cliente (guard "customer"), así que resolvemos el dueño según quien
    // tenga sesión activa.
    private function currentOwner(): ?array
    {
        if ($staff = Auth::guard('web')->user()) {
            return ['owner_type' => 'staff', 'owner_id' => $staff->id];
        }

        if ($customer = Auth::guard('customer')->user()) {
            return ['owner_type' => 'customer', 'owner_id' => $customer->id];
        }

        return null;
    }

    public function index()
    {
        $owner = $this->currentOwner();

        $items = Wishlist::where($owner)
            ->with('productColor.product')
            ->latest()
            ->get()
            ->map(function (Wishlist $w) {
                $color = $w->productColor;
                $p = $color->product;

                return [
                    'id' => $color->id,
                    'productId' => $p->id,
                    'name' => $p->name,
                    'colorNombre' => $color->name,
                    'price' => (float) $p->price,
                    'image' => PublicFile::url($color->image_url),
                ];
            })
            ->values();

        return Inertia::render('Shop/Wishlist', [
            'items' => $items,
        ]);
    }

    // Devuelve los IDs de color (product_color_id) en la wishlist de quien esté
    // logueado. Vacío si es invitado.
    public function ids()
    {
        $owner = $this->currentOwner();

        if (! $owner) {
            return response()->json([]);
        }

        return response()->json(Wishlist::where($owner)->pluck('product_color_id'));
    }

    public function toggle(ProductColor $color)
    {
        $owner = $this->currentOwner();

        if (! $owner) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $wishlist = Wishlist::where($owner)
            ->where('product_color_id', $color->id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();

            return response()->json(['added' => false]);
        }

        Wishlist::create($owner + ['product_color_id' => $color->id]);

        return response()->json(['added' => true]);
    }
}
