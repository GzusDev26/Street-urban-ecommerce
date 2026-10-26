<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Código de barras de cada variante (modelo × color × talla). Puede ser el de fábrica (se escanea al
// registrarlo) o uno propio "SU000123" para las prendas que no traen; el de fábrica reemplaza al propio.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('barcode', 64)->nullable()->unique()->after('sku');
        });

        // Las variantes que ya existen reciben su código propio, listo para etiquetar.
        DB::table('product_variants')->whereNull('barcode')->orderBy('id')->each(function ($v) {
            DB::table('product_variants')->where('id', $v->id)->update(['barcode' => 'SU' . str_pad($v->id, 6, '0', STR_PAD_LEFT)]);
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique(['barcode']);
            $table->dropColumn('barcode');
        });
    }
};
