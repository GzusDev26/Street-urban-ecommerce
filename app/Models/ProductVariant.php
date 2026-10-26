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

    // Quita espacios y deja el código en mayúsculas (los lectores a veces agregan espacios o saltos). Un UPC-A de 12
    // dígitos es el mismo código que el EAN-13 con un 0 delante: se guarda y se busca siempre con 13, así da igual
    // con cuál de los dos lo entregue la cámara o la pistola.
    public static function normalizarCodigo(string $codigo): string
    {
        $codigo = mb_strtoupper(preg_replace('/\s+/', '', $codigo));

        return preg_match('/^\d{12}$/', $codigo) ? '0' . $codigo : $codigo;
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
