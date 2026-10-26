<?php

use App\Support\NameFormatter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Lleva los nombres ya guardados (categorías, marcas, modelos y colores) al formato único de NameFormatter.
// Si el nombre formateado chocara con otro registro (índice único), ese se deja como está para revisarlo a mano.
return new class extends Migration
{
    public function up(): void
    {
        foreach (['categories', 'brands', 'products', 'product_colors'] as $tabla) {
            foreach (DB::table($tabla)->get(['id', 'name']) as $fila) {
                $nuevo = NameFormatter::format($fila->name);

                if ($nuevo === $fila->name) {
                    continue;
                }

                try {
                    DB::table($tabla)->where('id', $fila->id)->update(['name' => $nuevo]);
                } catch (\Illuminate\Database\QueryException) {
                    // nombre duplicado: se revisa manualmente
                }
            }
        }
    }

    public function down(): void
    {
        // El formato original no se puede recuperar.
    }
};
