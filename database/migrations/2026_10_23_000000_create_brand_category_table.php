<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Marcas dentro de categorías (Polos > Nike). Es una relación de muchos a muchos: la marca sigue
    // siendo una sola (Nike) y aparece en cada categoría donde se la agregue o tenga modelos. Así se
    // puede armar el árbol antes de comprar nada, sin duplicar marcas ni romper los reportes por marca.
    public function up(): void
    {
        Schema::create('brand_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['category_id', 'brand_id']);
        });

        // Los modelos que ya existen mantienen su lugar en el árbol.
        $ahora = now();
        $pares = DB::table('products')
            ->whereNotNull('brand_id')
            ->select('category_id', 'brand_id')
            ->distinct()
            ->get()
            ->map(fn ($p) => ['category_id' => $p->category_id, 'brand_id' => $p->brand_id, 'created_at' => $ahora, 'updated_at' => $ahora])
            ->all();

        if ($pares) {
            DB::table('brand_category')->insert($pares);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_category');
    }
};
