<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto de portada de la categoría para el Home de la tienda (ruta pública, ej. /image/shop/calzados.jpeg).
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('image')->nullable()->after('size_type');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
