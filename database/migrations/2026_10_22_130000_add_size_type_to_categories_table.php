<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cada categoría define qué tallas se ofrecen al comprar (ropa: S/M/L, pantalón: 28-40,
    // calzado: 35-45), en vez de adivinarlo por el nombre.
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('size_type', 12)->default('ropa')->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('size_type');
        });
    }
};
