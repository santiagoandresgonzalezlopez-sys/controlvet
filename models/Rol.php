<?php
/** Ruta: controlvet/models/Rol.php — Modelo de datos: roles */

class Rol
{
    public static function all(): array
    {
        return Database::getConnection()->query('SELECT * FROM roles ORDER BY id')->fetchAll();
    }
}
