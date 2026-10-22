<?php

namespace Database\Seeders;

use App\Services\CatalogBuilder;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    private const BRAND_STREET = 'Street Urban';
    private const BRAND_CORE = 'Urban Core';
    private const COLOR_DARK = '#111827';
    private const COLOR_LIGHT = '#F3F4F6';

    public function run(): void
    {
        $builder = app(CatalogBuilder::class);

        foreach ($this->definitions() as $definition) {
            $builder->create($definition);
        }
    }

    private function definitions(): array
    {
        return [
            [
                'name' => 'Camiseta Urban Básica',
                'brand' => self::BRAND_STREET,
                'category' => 'Camisetas',
                'description' => 'Camiseta de algodón con corte regular para uso diario.',
                'sku' => 'CAM-URB-001',
                'price' => 29.90,
                'cost' => 12.00,
                'image_url' => 'product-images/camiseta-urban-basica.svg',
                'colors' => [
                    [
                        'name' => 'Negro',
                        'hex' => self::COLOR_DARK,
                        'image_url' => 'product-colors/camiseta-urban-negro.svg',
                        'sizes' => [
                            ['size' => 'S', 'stock' => 4],
                            ['size' => 'M', 'stock' => 8],
                            ['size' => 'L', 'stock' => 6],
                            ['size' => 'XL', 'stock' => 2],
                        ],
                    ],
                    [
                        'name' => 'Blanco / Negro',
                        'hex' => self::COLOR_LIGHT,
                        'image_url' => 'product-colors/camiseta-urban-blanco-negro.svg',
                        'sizes' => [
                            ['size' => 'S', 'stock' => 3],
                            ['size' => 'M', 'stock' => 6],
                            ['size' => 'L', 'stock' => 5],
                            ['size' => 'XL', 'stock' => 2],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Camiseta Streetwear Premium',
                'brand' => self::BRAND_STREET,
                'category' => 'Camisetas',
                'description' => 'Modelo premium con tela más pesada y caída oversize.',
                'sku' => 'CAM-PREM-001',
                'price' => 49.90,
                'cost' => 20.00,
                'image_url' => 'product-images/camiseta-streetwear-premium.svg',
                'colors' => [
                    [
                        'name' => 'Negro',
                        'hex' => self::COLOR_DARK,
                        'image_url' => 'product-colors/camiseta-premium-negro.svg',
                        'sizes' => [
                            ['size' => 'S', 'stock' => 3],
                            ['size' => 'M', 'stock' => 5],
                            ['size' => 'L', 'stock' => 4],
                            ['size' => 'XL', 'stock' => 3],
                            ['size' => 'XXL', 'stock' => 2],
                        ],
                    ],
                    [
                        'name' => 'Azul Marino / Blanco',
                        'hex' => '#1E3A8A',
                        'image_url' => 'product-colors/camiseta-premium-azul-marino-blanco.svg',
                        'sizes' => [
                            ['size' => 'S', 'stock' => 2],
                            ['size' => 'M', 'stock' => 4],
                            ['size' => 'L', 'stock' => 4],
                            ['size' => 'XL', 'stock' => 2],
                            ['size' => 'XXL', 'stock' => 1],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Camiseta Oversized',
                'brand' => self::BRAND_CORE,
                'category' => 'Camisetas',
                'description' => 'Camiseta ancha, ideal para outfits relajados y streetwear.',
                'sku' => 'CAM-OVR-001',
                'price' => 39.90,
                'cost' => 16.00,
                'image_url' => 'product-images/camiseta-oversized.svg',
                'colors' => [
                    [
                        'name' => 'Beige',
                        'hex' => '#D6B98C',
                        'image_url' => 'product-colors/camiseta-oversized-beige.svg',
                        'sizes' => [
                            ['size' => 'S', 'stock' => 4],
                            ['size' => 'M', 'stock' => 6],
                            ['size' => 'L', 'stock' => 5],
                            ['size' => 'XL', 'stock' => 3],
                        ],
                    ],
                    [
                        'name' => 'Camuflaje Urbano',
                        'hex' => '#556B2F',
                        'image_url' => 'product-colors/camiseta-oversized-camuflaje.svg',
                        'sizes' => [
                            ['size' => 'S', 'stock' => 2],
                            ['size' => 'M', 'stock' => 4],
                            ['size' => 'L', 'stock' => 4],
                            ['size' => 'XL', 'stock' => 2],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Pantalón Jogger',
                'brand' => self::BRAND_STREET,
                'category' => 'Pantalones',
                'description' => 'Jogger con pretina elástica y fit cómodo para uso urbano.',
                'sku' => 'PAN-JOG-001',
                'price' => 59.90,
                'cost' => 24.00,
                'image_url' => 'product-images/pantalon-jogger.svg',
                'colors' => [
                    [
                        'name' => 'Negro',
                        'hex' => self::COLOR_DARK,
                        'image_url' => 'product-colors/pantalon-jogger-negro.svg',
                        'sizes' => [
                            ['size' => '28', 'stock' => 2],
                            ['size' => '30', 'stock' => 4],
                            ['size' => '32', 'stock' => 5],
                            ['size' => '34', 'stock' => 4],
                            ['size' => '36', 'stock' => 2],
                        ],
                    ],
                    [
                        'name' => 'Gris',
                        'hex' => '#9CA3AF',
                        'image_url' => 'product-colors/pantalon-jogger-gris.svg',
                        'sizes' => [
                            ['size' => '28', 'stock' => 1],
                            ['size' => '30', 'stock' => 3],
                            ['size' => '32', 'stock' => 4],
                            ['size' => '34', 'stock' => 3],
                            ['size' => '36', 'stock' => 1],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Pantalón Cargo',
                'brand' => self::BRAND_CORE,
                'category' => 'Pantalones',
                'description' => 'Cargo funcional con bolsillos laterales y acabado resistente.',
                'sku' => 'PAN-CAR-001',
                'price' => 69.90,
                'cost' => 28.00,
                'image_url' => 'product-images/pantalon-cargo.svg',
                'colors' => [
                    [
                        'name' => 'Beige',
                        'hex' => '#D6B98C',
                        'image_url' => 'product-colors/pantalon-cargo-beige.svg',
                        'sizes' => [
                            ['size' => '28', 'stock' => 2],
                            ['size' => '30', 'stock' => 3],
                            ['size' => '32', 'stock' => 4],
                            ['size' => '34', 'stock' => 2],
                            ['size' => '36', 'stock' => 1],
                        ],
                    ],
                    [
                        'name' => 'Camuflaje',
                        'hex' => '#4B5320',
                        'image_url' => 'product-colors/pantalon-cargo-camuflaje.svg',
                        'sizes' => [
                            ['size' => '28', 'stock' => 1],
                            ['size' => '30', 'stock' => 2],
                            ['size' => '32', 'stock' => 3],
                            ['size' => '34', 'stock' => 2],
                            ['size' => '36', 'stock' => 1],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Gorro Urban',
                'brand' => self::BRAND_STREET,
                'category' => 'Accesorios',
                'description' => 'Gorro de talla única con ajuste cómodo.',
                'sku' => 'GOR-URB-001',
                'price' => 19.90,
                'cost' => 7.50,
                'image_url' => 'product-images/gorro-urban.svg',
                'colors' => [
                    [
                        'name' => 'Negro',
                        'hex' => self::COLOR_DARK,
                        'image_url' => 'product-colors/gorro-urban-negro.svg',
                        'sizes' => [
                            ['size' => 'Único', 'stock' => 25],
                        ],
                    ],
                    [
                        'name' => 'Blanco / Negro',
                        'hex' => self::COLOR_LIGHT,
                        'image_url' => 'product-colors/gorro-urban-blanco-negro.svg',
                        'sizes' => [
                            ['size' => 'Único', 'stock' => 18],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Cinturón Piel',
                'brand' => 'Leather Line',
                'category' => 'Accesorios',
                'description' => 'Cinturón de cuero sintético con hebilla metálica.',
                'sku' => 'CIN-PIE-001',
                'price' => 24.90,
                'cost' => 9.50,
                'image_url' => 'product-images/cinturon-piel.svg',
                'colors' => [
                    [
                        'name' => 'Negro',
                        'hex' => self::COLOR_DARK,
                        'image_url' => 'product-colors/cinturon-negro.svg',
                        'sizes' => [
                            ['size' => '30', 'stock' => 6],
                            ['size' => '32', 'stock' => 8],
                            ['size' => '34', 'stock' => 8],
                            ['size' => '36', 'stock' => 6],
                        ],
                    ],
                    [
                        'name' => 'Marrón',
                        'hex' => '#7C4A2D',
                        'image_url' => 'product-colors/cinturon-marron.svg',
                        'sizes' => [
                            ['size' => '30', 'stock' => 4],
                            ['size' => '32', 'stock' => 6],
                            ['size' => '34', 'stock' => 6],
                            ['size' => '36', 'stock' => 4],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Zapatilla Alta',
                'brand' => self::BRAND_STREET,
                'category' => 'Calzado',
                'description' => 'Zapatilla de caña alta con suela de gran agarre.',
                'sku' => 'ZAP-ALT-001',
                'price' => 99.90,
                'cost' => 42.00,
                'image_url' => 'product-images/zapatilla-alta.svg',
                'colors' => [
                    [
                        'name' => 'Negro / Blanco',
                        'hex' => self::COLOR_DARK,
                        'image_url' => 'product-colors/zapatilla-alta-negro-blanco.svg',
                        'sizes' => [
                            ['size' => '38', 'stock' => 2],
                            ['size' => '39', 'stock' => 3],
                            ['size' => '40', 'stock' => 4],
                            ['size' => '41', 'stock' => 4],
                            ['size' => '42', 'stock' => 2],
                            ['size' => '43', 'stock' => 1],
                        ],
                    ],
                    [
                        'name' => 'Blanco / Rojo',
                        'hex' => self::COLOR_LIGHT,
                        'image_url' => 'product-colors/zapatilla-alta-blanco-rojo.svg',
                        'sizes' => [
                            ['size' => '38', 'stock' => 1],
                            ['size' => '39', 'stock' => 2],
                            ['size' => '40', 'stock' => 3],
                            ['size' => '41', 'stock' => 3],
                            ['size' => '42', 'stock' => 2],
                            ['size' => '43', 'stock' => 1],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Zapatilla Baja',
                'brand' => self::BRAND_CORE,
                'category' => 'Calzado',
                'description' => 'Zapatilla baja versátil para uso diario.',
                'sku' => 'ZAP-BAJ-001',
                'price' => 84.90,
                'cost' => 34.00,
                'image_url' => 'product-images/zapatilla-baja.svg',
                'colors' => [
                    [
                        'name' => 'Blanco',
                        'hex' => self::COLOR_LIGHT,
                        'image_url' => 'product-colors/zapatilla-baja-blanco.svg',
                        'sizes' => [
                            ['size' => '36', 'stock' => 2],
                            ['size' => '37', 'stock' => 3],
                            ['size' => '38', 'stock' => 4],
                            ['size' => '39', 'stock' => 5],
                            ['size' => '40', 'stock' => 4],
                            ['size' => '41', 'stock' => 3],
                            ['size' => '42', 'stock' => 2],
                            ['size' => '43', 'stock' => 1],
                            ['size' => '44', 'stock' => 1],
                        ],
                    ],
                    [
                        'name' => 'Gris / Azul',
                        'hex' => '#6B7280',
                        'image_url' => 'product-colors/zapatilla-baja-gris-azul.svg',
                        'sizes' => [
                            ['size' => '36', 'stock' => 1],
                            ['size' => '37', 'stock' => 2],
                            ['size' => '38', 'stock' => 3],
                            ['size' => '39', 'stock' => 4],
                            ['size' => '40', 'stock' => 3],
                            ['size' => '41', 'stock' => 2],
                            ['size' => '42', 'stock' => 1],
                            ['size' => '43', 'stock' => 1],
                            ['size' => '44', 'stock' => 1],
                        ],
                    ],
                ],
            ],
        ];
    }
}
