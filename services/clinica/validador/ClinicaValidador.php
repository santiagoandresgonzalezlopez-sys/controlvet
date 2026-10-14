<?php

declare(strict_types=1);

namespace ControlVet\Clinica\Validador;

use ControlVet\Kernel\Validation\Validator;

final class ClinicaValidador
{
    /** @param array<string,mixed> $d */
    public static function historia(array $d): void
    {
        (new Validator($d))
            ->required('mascota_id', 'paciente')->integer('mascota_id', 'paciente', 1)
            ->required('motivo_consulta', 'motivo de consulta')->maxLength('motivo_consulta', 200, 'motivo de consulta')
            ->required('diagnostico', 'diagnóstico')
            ->numeric('peso', 'peso')
            ->custom(($d['peso'] ?? '') === '' || !is_numeric($d['peso']) || ((float)$d['peso'] > 0 && (float)$d['peso'] < 1000), 'peso', 'El peso debe estar entre 0 y 1000 kg.')
            ->integer('cita_id', 'cita', 1)
            ->validate();
    }

    /** @param array<string,mixed> $d */
    public static function formula(array $d): void
    {
        (new Validator($d))
            ->required('medicamento', 'medicamento')->maxLength('medicamento', 150, 'medicamento')
            ->required('dosis', 'dosis')->maxLength('dosis', 80, 'dosis')
            ->required('frecuencia', 'frecuencia')->maxLength('frecuencia', 80, 'frecuencia')
            ->required('duracion', 'duración')->maxLength('duracion', 80, 'duración')
            ->validate();
    }

    /** @param array<string,mixed> $q */
    public static function filtros(array $q): void
    {
        (new Validator($q))
            ->integer('mascota_id', 'paciente', 1)
            ->integer('propietario_id', 'propietario', 1)
            ->integer('veterinario_id', 'veterinario', 1)
            ->integer('limite', 'límite', 1)
            ->validate();
    }
}
