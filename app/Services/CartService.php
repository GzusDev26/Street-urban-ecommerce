<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CartService
{
    public const TAX_RATE = 0.18;

    public function getOrCreateForCustomer(Customer $customer): Cart
    {
        return Cart::firstOrCreate(
            ['customer_id' => $customer->id, 'status' => 'active'],
        );
    }

    public function validateLines(array $lines): array
    {
        $normalized = collect($lines)
            ->filter(fn ($line) => is_array($line))
            ->map(fn ($line) => [
                'variant_id' => (int) ($line['variant_id'] ?? 0),
                'quantity' => (int) ($line['quantity'] ?? 0),
            ])
            ->filter(fn ($line) => $line['variant_id'] > 0 && $line['quantity'] > 0)
            ->groupBy('variant_id')
            ->map(fn (Collection $group) => [
                'variant_id' => $group->first()['variant_id'],
                'quantity' => $group->sum('quantity'),
            ])
            ->values();

        $items = [];
        $errors = [];

        foreach ($normalized as $line) {
            $variant = ProductVariant::with(['product.brand', 'product.category', 'productColor'])
                ->find($line['variant_id']);

            if (!$variant || !$variant->product || !$variant->product->active) {
                $errors[] = [
                    'variant_id' => $line['variant_id'],
                    'code' => 'UNAVAILABLE',
                    'message' => 'Este producto ya no está disponible.',
                ];
                continue;
            }

            $price = (float) ($variant->price ?? $variant->product->price);
            $availableStock = (int) $variant->stock;

            if ($line['quantity'] > $availableStock) {
                $errors[] = [
                    'variant_id' => $variant->id,
                    'code' => 'INSUFFICIENT_STOCK',
                    'requested_quantity' => $line['quantity'],
                    'available_stock' => $availableStock,
                    'message' => $availableStock > 0
                        ? "Solo quedan {$availableStock} unidades disponibles."
                        : 'Esta variante está agotada.',
                ];
            }

            $items[] = [
                'variant_id' => $variant->id,
                'product_id' => $variant->product_id,
                'name' => $variant->product->name,
                'description' => $variant->product->description,
                'brand' => $variant->product->brand?->name,
                'category' => $variant->product->category?->name,
                'color_id' => $variant->productColor?->id,
                'color' => $variant->productColor?->name,
                'color_hex' => $variant->productColor?->hex,
                'size' => $variant->size,
                'image' => $variant->productColor?->image_url
                    ? Storage::disk('public')->url($variant->productColor->image_url)
                    : null,
                'quantity' => $line['quantity'],
                'available_stock' => $availableStock,
                'unit_price' => round($price, 2),
                'subtotal' => round($price * $line['quantity'], 2),
            ];
        }

        $subtotal = round(collect($items)->sum('subtotal'), 2);
        $tax = round($subtotal - ($subtotal / (1 + self::TAX_RATE)), 2);

        return [
            'valid' => empty($errors),
            'items' => array_values($items),
            'errors' => $errors,
            'summary' => [
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => 0,
                'total' => $subtotal,
                'total_items' => collect($items)->sum('quantity'),
            ],
        ];
    }

    public function syncCustomerCart(Customer $customer, array $lines): array
    {
        return DB::transaction(function () use ($customer, $lines) {
            $cart = $this->getOrCreateForCustomer($customer);
            $current = $cart->items()->pluck('quantity', 'product_variant_id')->map(fn ($quantity) => (int) $quantity)->all();

            foreach ($lines as $line) {
                $variantId = (int) ($line['variant_id'] ?? 0);
                $quantity = (int) ($line['quantity'] ?? 0);

                if ($variantId <= 0 || $quantity <= 0) {
                    continue;
                }

                $current[$variantId] = ($current[$variantId] ?? 0) + $quantity;
            }

            $validated = $this->validateLines(
                collect($current)->map(fn ($quantity, $variantId) => [
                    'variant_id' => (int) $variantId,
                    'quantity' => $quantity,
                ])->values()->all(),
            );

            $cart->items()->delete();
            foreach ($validated['items'] as $item) {
                if ($item['available_stock'] <= 0) {
                    continue;
                }

                $cart->items()->create([
                    'product_variant_id' => $item['variant_id'],
                    'quantity' => min($item['quantity'], $item['available_stock']),
                ]);
            }

            $persisted = $this->serializePersistedCart($cart->fresh());

            return [
                'cart_id' => $cart->id,
                ...$persisted,
                'valid' => $validated['valid'],
                'errors' => $validated['errors'],
            ];
        });
    }

    public function add(Customer $customer, int $variantId, int $quantity): array
    {
        return DB::transaction(function () use ($customer, $variantId, $quantity) {
            $cart = $this->getOrCreateForCustomer($customer);
            $item = $cart->items()->firstOrNew(['product_variant_id' => $variantId]);
            $newQuantity = ($item->quantity ?? 0) + $quantity;
            $validated = $this->validateLines([[
                'variant_id' => $variantId,
                'quantity' => $newQuantity,
            ]]);

            if (!$validated['valid']) {
                return [
                    ...$this->serializePersistedCart($cart),
                    'valid' => false,
                    'errors' => $validated['errors'],
                ];
            }

            $item->quantity = $newQuantity;
            $item->save();

            return [
                ...$this->serializePersistedCart($cart->fresh()),
                'valid' => true,
                'errors' => [],
            ];
        });
    }

    public function update(Customer $customer, int $variantId, int $quantity): array
    {
        return DB::transaction(function () use ($customer, $variantId, $quantity) {
            $cart = $this->getOrCreateForCustomer($customer);
            $item = $cart->items()->where('product_variant_id', $variantId)->firstOrFail();
            $validated = $this->validateLines([[
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ]]);

            if (!$validated['valid']) {
                return [
                    ...$this->serializePersistedCart($cart),
                    'valid' => false,
                    'errors' => $validated['errors'],
                ];
            }

            $item->update(['quantity' => $quantity]);

            return [
                ...$this->serializePersistedCart($cart->fresh()),
                'valid' => true,
                'errors' => [],
            ];
        });
    }

    public function remove(Customer $customer, int $variantId): array
    {
        $cart = $this->getOrCreateForCustomer($customer);
        $cart->items()->where('product_variant_id', $variantId)->delete();

        return $this->serializePersistedCart($cart->fresh());
    }

    private function serializePersistedCart(Cart $cart): array
    {
        $cart->loadMissing('items');

        $lines = $cart->items->map(fn (CartItem $item) => [
            'variant_id' => $item->product_variant_id,
            'quantity' => $item->quantity,
        ])->values()->all();

        $payload = $this->validateLines($lines);

        return [
            'cart_id' => $cart->id,
            ...$payload,
        ];
    }
}
