<?php

declare(strict_types=1);

namespace ControlVet\Inventario\Validador;

use ControlVet\Kernel\Validation\Validator;

final class ProductoValidador
{
    /** @param array<string,mixed> $d */
    public static function guardar(array $d, bool $esNuevo): void
    {
        $v = (new Validator($d))
            ->required('nombre', 'nombre')->maxLength('nombre', 120, 'nombre')
            ->required('categoria', 'categoría')->maxLength('categoria', 60, 'categoría')
            ->maxLength('tipo', 60, 'tipo')
            ->required('stock_minimo', 'stock mínimo')->numeric('stock_minimo', 'stock mínimo')->integer('stock_minimo', 'stock mínimo', 0)
            ->numeric('precio_referencia', 'precio de referencia')
            ->custom(($d['precio_referencia'] ?? '') === '' || !is_numeric($d['precio_referencia']) || (float)$d['precio_referencia'] >= 0, 'precio_referencia', 'El precio de referencia no puede ser negativo.');

        if ($esNuevo) {
            $v->required('cantidad_disponible', 'cantidad disponible')
              ->numeric('cantidad_disponible', 'cantidad disponible')
              ->integer('cantidad_disponible', 'cantidad disponible', 0);
        }
        $v->validate();
    }

    /** @param array<string,mixed> $d */
    public static function stock(array $d): void
    {
        (new Validator($d))
            ->required('cantidad_disponible', 'cantidad disponible')
            ->integer('cantidad_disponible', 'cantidad disponible', 0)
            ->validate();
    }
}
