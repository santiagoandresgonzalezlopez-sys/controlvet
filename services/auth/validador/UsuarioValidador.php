<?php

declare(strict_types=1);

namespace ControlVet\Auth\Validador;

use ControlVet\Kernel\Validation\Validator;

/**
 * Validaciones de entrada del servicio de autenticación (servidor).
 */
final class UsuarioValidador
{
    /** @param array<string,mixed> $data */
    public static function login(array $data): void
    {
        (new Validator($data))
            ->required('email', 'correo electrónico')
            ->email('email')
            ->required('password', 'contraseña')
            ->validate();
    }

    /** @param array<string,mixed> $data */
    public static function guardar(array $data, bool $esNuevo): void
    {
        $v = (new Validator($data))
            ->required('nombre', 'nombre')->maxLength('nombre', 80, 'nombre')
            ->required('apellido', 'apellido')->maxLength('apellido', 80, 'apellido')
            ->required('email', 'correo electrónico')->email('email')->maxLength('email', 120, 'correo electrónico')
            ->required('rol_id', 'rol')->integer('rol_id', 'rol', 1)
            ->integer('propietario_id', 'propietario', 1);

        if ($esNuevo) {
            $v->required('password', 'contraseña');
        }
        if (!empty($data['password'])) {
            $v->minLength('password', 6, 'contraseña')->maxLength('password', 72, 'contraseña');
        }
        $v->validate();
    }

    /** @param array<string,mixed> $data */
    public static function estado(array $data): void
    {
        (new Validator($data))
            ->required('estado', 'estado')
            ->in('estado', ['activo', 'inactivo'], 'estado')
            ->validate();
    }
}
