<?php
/**
 * Ruta: controlvet/web/config/env.php
 *
 * Configuración del frontend leída de web/config/web.env (KEY=VALOR, no
 * versionado). En XAMPP el frontend y los microservicios comparten el mismo
 * proceso de Apache, por eso los valores se guardan en memoria de la
 * petición y NO con putenv() (que es global al proceso y mezclaría la
 * configuración entre peticiones simultáneas).
 */

function cv_env(string $clave, string $porDefecto = ''): string
{
    static $valores = null;
    if ($valores === null) {
        $valores = [];
        $archivo = __DIR__ . '/web.env';
        foreach (is_file($archivo) ? (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) : [] as $linea) {
            $linea = trim($linea);
            if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) {
                continue;
            }
            [$k, $v] = array_map('trim', explode('=', $linea, 2));
            if (preg_match('/^[A-Z][A-Z0-9_]*$/', $k)) {
                $valores[$k] = trim($v, "\"'");
            }
        }
    }
    if (isset($valores[$clave])) {
        return $valores[$clave];
    }
    $env = getenv($clave);
    return $env === false || $env === '' ? $porDefecto : $env;
}
