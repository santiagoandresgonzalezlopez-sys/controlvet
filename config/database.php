<?php
/**
 * Configuración centralizada de conexión a la base de datos.
 * Ruta: controlvet/config/database.php
 * Requerimiento 18: credenciales de conexión en un único archivo.
 */

return [
    'host'     => 'localhost',
    'port'     => '3306',
    'dbname'   => 'controlvet',
    'user'     => 'root',
    'password' => '', // Vacía por defecto en XAMPP. Cambiar si se configuró una.
    'charset'  => 'utf8mb4',
];
