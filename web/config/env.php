<?php
/**
 * Ruta: controlvet/web/config/env.php
 *
 * Configuración del frontend leída de web/config/web.php (arreglo PHP generado
 * por infra/xampp/instalar.php, no versionado; si se pidiera por URL se
 * ejecuta sin mostrar nada). En XAMPP el frontend y los microservicios
 * comparten el mismo proceso de Apache, por eso los valores se guardan en
 * memoria de la petición y NO con putenv() (global al proceso).
 */

function cv_env(string $clave, string $porDefecto = ''): string
{
    static $valores = null;
    if ($valores === null) {
        $archivo = __DIR__ . '/web.php';
        $datos = is_file($archivo) ? require $archivo : [];
        $valores = is_array($datos) ? array_map('strval', array_filter($datos, 'is_scalar')) : [];
    }
    if (isset($valores[$clave])) {
        return $valores[$clave];
    }
    $env = getenv($clave);
    return $env === false || $env === '' ? $porDefecto : $env;
}

// La zona horaria es de la aplicación, no del php.ini del servidor (XAMPP trae Europe/Berlin).
date_default_timezone_set(cv_env('APP_TIMEZONE', 'America/Bogota'));
