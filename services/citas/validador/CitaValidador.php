<?php

declare(strict_types=1);

namespace ControlVet\Citas\Validador;

use ControlVet\Citas\Microservicio\CitaService;
use ControlVet\Kernel\Validation\Validator;

final class CitaValidador
{
    /** @param array<string,mixed> $d */
    public static function guardar(array $d): void
    {
        (new Validator($d))
            ->required('mascota_id', 'paciente')
            ->custom(!empty($d['mascota_id']), 'mascota_id', 'No es posible registrar una cita sin paciente.')
            ->integer('mascota_id', 'paciente', 1)
            ->required('veterinario_id', 'veterinario')
            ->custom(!empty($d['veterinario_id']), 'veterinario_id', 'No es posible registrar una cita sin veterinario.')
            ->integer('veterinario_id', 'veterinario', 1)
            ->required('fecha', 'fecha')
            ->date('fecha', 'la cita')
            ->required('hora', 'hora')
            ->time('hora', 'la cita')
            ->required('motivo', 'motivo')
            ->maxLength('motivo', 200, 'motivo')
            ->validate();
    }

    /** @param array<string,mixed> $d */
    public static function estado(array $d): void
    {
        (new Validator($d))
            ->required('estado', 'estado')
            ->in('estado', CitaService::ESTADOS, 'estado')
            ->validate();
    }

    /** @param array<string,mixed> $q */
    public static function filtros(array $q): void
    {
        (new Validator($q))
            ->date('fecha', 'filtro')
            ->in('estado', CitaService::ESTADOS, 'estado')
            ->integer('veterinario_id', 'veterinario', 1)
            ->integer('propietario_id', 'propietario', 1)
            ->integer('limite', 'límite', 1)
            ->validate();
    }
}
