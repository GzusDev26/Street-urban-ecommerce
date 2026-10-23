<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Detecta nombres "casi iguales" para no acabar con "Polo", "Polos" y "polos" como tres
// categorías distintas (ni "Nike" y "Nikes" como dos marcas). La coincidencia exacta ya la
// resuelven los índices únicos; esto atrapa lo que se escribe distinto a propósito o por error.
class NameMatcher
{
    // Formas comparables de un nombre: sin tildes, mayúsculas, espacios ni signos, y con las posibles
    // versiones en singular (sin la "s" o la "es" final). Se prueban todas porque no se sabe cuál es la
    // correcta: "pantalones" → "pantalon" (quita "es"), pero "converses" → "converse" (quita solo "s").
    public static function keys(string $name): array
    {
        $base = Str::of(Str::ascii($name))->lower()->replaceMatches('/[^a-z0-9]+/', '')->toString();
        $formas = [$base];

        foreach (['s', 'es'] as $sufijo) {
            if (strlen($base) > 3 && str_ends_with($base, $sufijo) && strlen($base) - strlen($sufijo) >= 3) {
                $formas[] = substr($base, 0, -strlen($sufijo));
            }
        }

        return array_values(array_unique($formas));
    }

    /**
     * Registro existente cuyo nombre es "casi igual" al dado pero no idéntico.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function similar(string $modelClass, string $name, ?int $ignoreId = null): ?Model
    {
        $buscadas = self::keys($name);
        $exacto = mb_strtolower(trim($name));

        return $modelClass::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->get(['id', 'name'])
            ->first(fn ($item) => array_intersect($buscadas, self::keys($item->name)) !== [] && mb_strtolower(trim($item->name)) !== $exacto);
    }
}
