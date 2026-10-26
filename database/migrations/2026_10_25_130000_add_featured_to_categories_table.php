<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categorías destacadas: las únicas que salen en la vitrina del Home. Las que se creen
     * después desde el admin entran sin destacar, para no romper la composición de portada.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('featured')->default(false)->after('image');
        });

        // Las 5 categorías base de CategorySeeder, si ya existen, quedan como la vitrina actual.
        DB::table('categories')
            ->whereIn('slug', ['zapatillas', 'polos', 'poleras', 'pantalones', 'gorras'])
            ->update(['featured' => true]);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('featured');
        });
    }
};
