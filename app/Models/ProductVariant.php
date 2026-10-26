<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'product_color_id', 'size', 'sku', 'barcode', 'stock', 'cost', 'price'];

    protected $casts = [
        'cost' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    // Prefijo de los códigos propios: los de fábrica son solo números, así que nunca chocan.
    public const PREFIJO_CODIGO = 'SU';

    protected static function booted(): void
    {
        // Toda variante nueva nace con su código propio (sale de su id), para poder etiquetarla de inmediato.
        static::created(function (self $variant) {
            if (! $variant->barcode) {
                $variant->forceFill(['barcode' => self::codigoPropio($variant->id)])->saveQuietly();
            }
        });
    }

    public static function codigoPropio(int $id): string
    {
        return self::PREFIJO_CODIGO . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    // Quita espacios y deja el código en mayúsculas (los lectores a veces agregan espacios o saltos).
    public static function normalizarCodigo(string $codigo): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', $codigo));
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productColor()
    {
        return $this->belongsTo(ProductColor::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
