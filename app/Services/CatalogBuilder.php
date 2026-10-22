<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogBuilder
{
    public function create(array $definition): Product
    {
        return DB::transaction(function () use ($definition) {
            $brand = Brand::firstOrCreate(
                ['slug' => Str::slug($definition['brand'])],
                [
                    'name' => $definition['brand'],
                    'description' => $definition['brand_description'] ?? null,
                    'active' => true,
                ]
            );

            $category = Category::firstOrCreate(
                ['slug' => Str::slug($definition['category'])],
                [
                    'name' => $definition['category'],
                    'description' => $definition['category_description'] ?? null,
                    'active' => true,
                ]
            );

            $product = Product::create([
                'name' => $definition['name'],
                'brand_id' => $brand->id,
                'description' => $definition['description'] ?? null,
                'sku' => $definition['sku'],
                'price' => $definition['price'],
                'cost' => $definition['cost'] ?? null,
                'stock' => 0,
                'category_id' => $category->id,
                'image_url' => $definition['image_url'] ?? null,
                'active' => $definition['active'] ?? true,
            ]);

            $colors = $definition['colors'] ?? [];
            if (count($colors) === 0) {
                $colors = [[
                    'name' => 'Único',
                    'hex' => null,
                    'image_url' => null,
                    'sizes' => [[
                        'size' => 'Única',
                        'stock' => $definition['stock'] ?? 0,
                        'price' => $definition['price'],
                        'cost' => $definition['cost'] ?? null,
                    ]],
                ]];
            }

            $totalStock = 0;

            foreach ($colors as $colorDefinition) {
                $color = $product->colors()->create([
                    'name' => $colorDefinition['name'],
                    'hex' => $colorDefinition['hex'] ?? null,
                    'image_url' => $colorDefinition['image_url'] ?? null,
                ]);

                foreach ($colorDefinition['sizes'] as $sizeDefinition) {
                    $variantStock = (int) $sizeDefinition['stock'];
                    $totalStock += $variantStock;

                    $product->variants()->create([
                        'product_color_id' => $color->id,
                        'size' => $sizeDefinition['size'],
                        'sku' => $sizeDefinition['sku'] ?? $this->makeVariantSku($product->sku, $color->name, $sizeDefinition['size']),
                        'stock' => $variantStock,
                        'cost' => $sizeDefinition['cost'] ?? $product->cost,
                        'price' => $sizeDefinition['price'] ?? $product->price,
                    ]);
                }
            }

            $product->update(['stock' => $totalStock]);

            return $product->load(['brand', 'category', 'colors.variants']);
        });
    }

    private function makeVariantSku(string $productSku, string $colorName, string $size): string
    {
        return Str::of($productSku)
            ->append('-', Str::slug($colorName), '-', Str::slug((string) $size))
            ->upper()
            ->toString();
    }
}
