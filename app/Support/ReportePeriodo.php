<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

// Piezas de tiempo de los reportes: la serie por día (o por mes si el rango es largo) y la comparación con el
// período anterior de la misma duración.
class ReportePeriodo
{
    // Más de este rango (en días) se agrupa por mes para que el gráfico no sea una maraña.
    private const MAX_DIAS_DIARIO = 92;

    /**
     * @param  Collection  $porDia  líneas agrupadas por 'Y-m-d'
     * @param  callable  $medir  recibe las líneas de un día/mes y devuelve ['medida' => valor, ...]
     * @param  array  $vacio  la misma forma con ceros, para los días sin movimiento
     * @return array{granularidad: string, puntos: array}
     */
    public static function serie(Collection $porDia, ?string $desde, ?string $hasta, callable $medir, array $vacio): array
    {
        if ($porDia->isEmpty()) {
            return ['granularidad' => 'dia', 'puntos' => []];
        }

        $inicio = Carbon::parse($desde ?: $porDia->keys()->min())->startOfDay();
        $fin = Carbon::parse($hasta ?: $porDia->keys()->max())->startOfDay();
        $mensual = $inicio->diffInDays($fin) > self::MAX_DIAS_DIARIO;

        if ($mensual) {
            $inicio = $inicio->copy()->startOfMonth();
            $fin = $fin->copy()->startOfMonth();
        }

        $grupos = $porDia->groupBy(fn ($g, $dia) => $mensual ? substr($dia, 0, 7) : $dia)->map(fn ($dias) => $dias->flatten(1));

        $puntos = [];
        for ($c = $inicio->copy(); $c->lte($fin); $mensual ? $c->addMonth() : $c->addDay()) {
            $clave = $c->format($mensual ? 'Y-m' : 'Y-m-d');
            $puntos[] = ['clave' => $clave] + ($grupos->has($clave) ? $medir($grupos[$clave]) : $vacio);
        }

        return ['granularidad' => $mensual ? 'mes' : 'dia', 'puntos' => $puntos];
    }

    /**
     * Medidas del período inmediatamente anterior, de la misma duración. Solo existe si el rango tiene
     * inicio y fin (sin ellos no hay "anterior" que comparar).
     */
    public static function anterior(?string $desde, ?string $hasta, callable $medir): ?array
    {
        if (! $desde || ! $hasta) {
            return null;
        }

        $inicio = Carbon::parse($desde)->startOfDay();
        $fin = Carbon::parse($hasta)->startOfDay();
        $dias = $inicio->diffInDays($fin) + 1;

        $prevFin = $inicio->copy()->subDay();
        $prevInicio = $prevFin->copy()->subDays($dias - 1);

        return [
            'desde' => $prevInicio->format('Y-m-d'),
            'hasta' => $prevFin->format('Y-m-d'),
            'medidas' => $medir($prevInicio->format('Y-m-d'), $prevFin->format('Y-m-d')),
        ];
    }
}
