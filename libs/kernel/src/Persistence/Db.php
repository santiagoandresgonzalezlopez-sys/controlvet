<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Persistence;

use PDO;

/**
 * Fábrica de conexiones PDO. Cada servicio la invoca con SUS PROPIAS
 * variables de entorno (DB_NAME/DB_USER/DB_PASSWORD): el usuario de base
 * de datos de un servicio solo tiene privilegios sobre su esquema, por lo
 * que no puede leer ni escribir datos de otro servicio.
 */
final class Db
{
    public static function fromEnv(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST') ?: 'db',
            getenv('DB_PORT') ?: '3306',
            (string)getenv('DB_NAME'),
        );

        return new PDO($dsn, (string)getenv('DB_USER'), (string)getenv('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 3,
        ]);
    }
}
