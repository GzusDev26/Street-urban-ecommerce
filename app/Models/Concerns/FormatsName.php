<?php

namespace App\Models\Concerns;

use App\Support\NameFormatter;

// Aplica NameFormatter al guardar, para que ninguna vía de alta o edición (admin, compras, seeders)
// deje un nombre con otro formato.
trait FormatsName
{
    protected static function bootFormatsName(): void
    {
        static::saving(function ($model) {
            if (is_string($model->name) && $model->isDirty('name')) {
                $model->name = NameFormatter::format($model->name);
            }
        });
    }
}
