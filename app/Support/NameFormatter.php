<?php

namespace App\Support;

// Formato único para los nombres del catálogo: cada palabra con la primera letra en mayúscula y el
// resto en minúscula ("PANTALON", "pantalon" y "Pantalon" quedan igual: "Pantalon"; "azul noche" → "Azul Noche").
class NameFormatter
{
    public static function format(string $name): string
    {
        $limpio = trim(preg_replace('/\s+/u', ' ', $name));

        return mb_convert_case($limpio, MB_CASE_TITLE, 'UTF-8');
    }
}
