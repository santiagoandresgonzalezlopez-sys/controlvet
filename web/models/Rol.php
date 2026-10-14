<?php
/** Ruta: controlvet/web/models/Rol.php — Cliente del microservicio auth (roles) */

class Rol
{
    public static function all(): array
    {
        return Api::get('auth', '/roles') ?? [];
    }
}
