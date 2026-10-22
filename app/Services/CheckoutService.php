<?php

namespace App\Services;

use App\Exceptions\CartStockException;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CheckoutService
{
    public function confirm(?Customer $customer, array $payload): array
    {
        return DB::transaction(function () use ($customer, $payload) {
            $cart = $customer
                ? Cart::where('customer_id', $customer->id)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first()
                : null;

            $lines = $customer
                ? ($cart?->items()->lockForUpdate()->get()->map(fn ($item) => [
                    'variant_id' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                ])->all() ?? [])
                : ($payload['items'] ?? []);

            if ($lines === []) {
                abort(422, 'El carrito está vacío.');
            }

            $normalized = collect($lines)
                ->map(fn ($line) => [
                    'variant_id' => (int) ($line['variant_id'] ?? $line['variantId'] ?? 0),
                    'quantity' => (int) ($line['quantity'] ?? $line['cantidad'] ?? 0),
                ])
                ->groupBy(fn ($line) => $line['variant_id'])
                ->map(fn ($group, $variantId) => [
                    'variant_id' => (int) $variantId,
                    'quantity' => $group->sum('quantity'),
                ])
                ->filter(fn ($line) => $line['variant_id'] > 0 && $line['quantity'] > 0)
                ->sortBy('variant_id')
                ->values();

            $variants = ProductVariant::with('product')
                ->whereIn('id', $normalized->pluck('variant_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $errors = [];
            $items = [];

            foreach ($normalized as $line) {
                $variant = $variants->get($line['variant_id']);

                if (!$variant || !$variant->product || !$variant->product->active) {
                    $errors[] = [
                        'variant_id' => $line['variant_id'],
                        'code' => 'UNAVAILABLE',
                        'message' => 'Este producto ya no está disponible.',
                    ];
                    continue;
                }

                if ($line['quantity'] > $variant->stock) {
                    $errors[] = [
                        'variant_id' => $variant->id,
                        'code' => 'INSUFFICIENT_STOCK',
                        'requested_quantity' => $line['quantity'],
                        'available_stock' => (int) $variant->stock,
                        'message' => $variant->stock > 0
                            ? "Solo quedan {$variant->stock} unidades disponibles."
                            : 'Esta variante está agotada.',
                    ];
                    continue;
                }

                $unitPrice = (float) ($variant->price ?? $variant->product->price);
                $items[] = [
                    'variant' => $variant,
                    'quantity' => $line['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => round($unitPrice * $line['quantity'], 2),
                ];
            }

            if ($errors !== []) {
                throw new CartStockException($errors);
            }

            $buyer = $customer ?: $this->findOrCreateGuestCustomer($payload);
            $total = round(collect($items)->sum('subtotal'), 2);

            $sale = Sale::create([
                'user_id' => null,
                'sale_date' => now()->toDateString(),
                'customer_name' => $buyer->name,
                'customer_id' => $buyer->id,
                'notes' => 'Venta web',
                'total' => $total,
            ]);

            foreach ($items as $item) {
                /** @var ProductVariant $variant */
                $variant = $item['variant'];
                $quantity = $item['quantity'];

                $variant->decrement('stock', $quantity);
                $variant->product->decrement('stock', $quantity);

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $variant->cost ?? 0,
                    'subtotal' => $item['subtotal'],
                ]);
            }

            if ($cart) {
                $cart->items()->delete();
            }

            return [
                'sale_id' => $sale->id,
                'sale_number' => 'V-' . str_pad($sale->id, 5, '0', STR_PAD_LEFT),
                'total' => $total,
                'message' => 'Pedido confirmado correctamente.',
            ];
        });
    }

    private function findOrCreateGuestCustomer(array $payload): Customer
    {
        $customer = Customer::where('email', $payload['email'])->first();

        if ($customer) {
            $customer->update([
                'name' => $payload['name'],
                'phone' => $payload['phone'] ?? $customer->phone,
            ]);

            return $customer;
        }

        return Customer::create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => Hash::make(Str::random(40)),
            'phone' => $payload['phone'] ?? null,
        ]);
    }
}
