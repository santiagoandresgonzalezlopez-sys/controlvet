<?php
/**
 * Ruta: controlvet/core/Database.php
 * Envoltorio PDO (Singleton) para acceso a MySQL con consultas preparadas.
 */

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";

            try {
                self::$instance = new PDO($dsn, $config['user'], $config['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Requerimiento 14: manejo de errores de conexión a MySQL sin exponer detalles internos.
                error_log('Error de conexión a MySQL: ' . $e->getMessage());
                http_response_code(500);
                die('No fue posible conectar con la base de datos. Verifique que Apache y MySQL estén activos en XAMPP.');
            }
        }

        return self::$instance;
    }
}
