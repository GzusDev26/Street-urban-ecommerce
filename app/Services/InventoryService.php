<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemBatch;

// Salida de inventario compartida por la venta en mostrador y por la venta web, para que las
// dos descuenten stock y consuman lotes exactamente igual (si una se desviara, el valor de
// inventario y la cancelación de compras dejarían de cuadrar).
class InventoryService
{
    // Descuenta stock y registra la línea de venta con su costo real. La variante debe venir
    // bloqueada (lockForUpdate) y con el stock ya validado por quien llama, dentro de su transacción.
    public function sell(Sale $sale, ProductVariant $variant, int $cantidad, float $precioUnitario): SaleItem
    {
        $variant->decrement('stock', $cantidad);
        $variant->product->decrement('stock', $cantidad);

        $saleItem = SaleItem::create([
            'sale_id' => $sale->id,
            'product_variant_id' => $variant->id,
            'quantity' => $cantidad,
            'unit_price' => $precioUnitario,
            'unit_cost' => 0,
            'subtotal' => round($cantidad * $precioUnitario, 2),
        ]);

        $costoTotal = $this->consumeLots($saleItem, $variant, $cantidad);

        $saleItem->update(['unit_cost' => $cantidad > 0 ? $costoTotal / $cantidad : 0]);

        return $saleItem;
    }

    // Consumo FIFO de los lotes de compra, para que el valor de inventario (Compras > Inventario)
    // siga reflejando solo lo que realmente queda. Se registra de qué lote(s) salió cada unidad,
    // para poder revertirlo si la venta se cancela o se devuelve. Devuelve el costo total consumido.
    private function consumeLots(SaleItem $saleItem, ProductVariant $variant, int $cantidad): float
    {
        $porConsumir = $cantidad;
        $costoConsumido = 0;

        $lotes = PurchaseItem::where('product_variant_id', $variant->id)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('created_at')
            ->get();

        foreach ($lotes as $lote) {
            if ($porConsumir <= 0) {
                break;
            }

            $tomar = min($porConsumir, $lote->quantity_remaining);
            $lote->decrement('quantity_remaining', $tomar);
            $costoConsumido += $tomar * $lote->unit_cost;
            $porConsumir -= $tomar;

            SaleItemBatch::create([
                'sale_item_id' => $saleItem->id,
                'purchase_item_id' => $lote->id,
                'quantity' => $tomar,
            ]);
        }

        // Si no hay lotes suficientes registrados (ej. stock anterior al seguimiento por lotes),
        // se usa el costo de referencia de la variante para lo restante — esa porción no queda
        // ligada a ningún lote porque no salió de ninguno.
        if ($porConsumir > 0) {
            $costoConsumido += $porConsumir * $variant->cost;
        }

        return $costoConsumido;
    }
}
