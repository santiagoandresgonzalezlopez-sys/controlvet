<?php

declare(strict_types=1);

namespace ControlVet\Pacientes\Validador;

use ControlVet\Kernel\Validation\Validator;

final class PacientesValidador
{
    /** @param array<string,mixed> $d */
    public static function propietario(array $d): void
    {
        (new Validator($d))
            ->required('nombre', 'nombre')->maxLength('nombre', 80, 'nombre')
            ->required('apellido', 'apellido')->maxLength('apellido', 80, 'apellido')
            ->required('documento', 'documento')->maxLength('documento', 30, 'documento')
            ->required('telefono', 'teléfono')->maxLength('telefono', 20, 'teléfono')
            ->email('email')->maxLength('email', 120, 'correo electrónico')
            ->maxLength('direccion', 180, 'dirección')
            ->validate();
    }

    /** @param array<string,mixed> $d */
    public static function mascota(array $d): void
    {
        (new Validator($d))
            ->required('nombre', 'nombre')->maxLength('nombre', 80, 'nombre')
            ->required('especie', 'especie')->maxLength('especie', 40, 'especie')
            ->required('sexo', 'sexo')->in('sexo', ['macho', 'hembra'], 'sexo')
            ->required('propietario_id', 'propietario')
            ->custom(!empty($d['propietario_id']), 'propietario_id', 'No es posible registrar una mascota sin propietario.')
            ->integer('propietario_id', 'propietario', 1)
            ->numeric('peso_actual', 'peso')
            ->custom(($d['peso_actual'] ?? '') === '' || !is_numeric($d['peso_actual']) || (float)$d['peso_actual'] >= 0, 'peso_actual', 'El peso no puede ser negativo.')
            ->date('fecha_nacimiento', 'nacimiento')
            ->custom(empty($d['fecha_nacimiento']) || (string)$d['fecha_nacimiento'] <= date('Y-m-d'), 'fecha_nacimiento', 'La fecha de nacimiento no puede ser futura.')
            ->maxLength('raza', 60, 'raza')
            ->maxLength('color', 40, 'color')
            ->validate();
    }

    /** @param array<string,mixed> $d */
    public static function peso(array $d): void
    {
        (new Validator($d))
            ->required('peso', 'peso')
            ->numeric('peso', 'peso')
            ->custom(!is_numeric($d['peso'] ?? null) || ((float)$d['peso'] > 0 && (float)$d['peso'] < 1000), 'peso', 'El peso debe estar entre 0 y 1000 kg.')
            ->validate();
    }
}
