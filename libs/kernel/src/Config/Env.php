<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Config;

/**
 * Configuración de un microservicio.
 *
 * En XAMPP todos los servicios se ejecutan dentro del MISMO proceso de
 * Apache (hilos de mpm_winnt): putenv() es global al proceso y mezclaría la
 * configuración de servicios que atienden peticiones a la vez. Por eso los
 * valores de config/servicio.env se guardan en memoria de la petición
 * (estática por hilo) y nunca en el entorno del proceso. Si no hay archivo
 * (p. ej. variables definidas por el sistema), se recurre a getenv().
 */
final class Env
{
    /** @var array<string,string> */
    private static array $valores = [];

    public static function load(string $file): void
    {
        self::$valores = [];
        if (!is_file($file) || !is_readable($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if (preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                self::$valores[$key] = trim($value, "\"'");
            }
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        if (isset(self::$valores[$key])) {
            return self::$valores[$key];
        }
        $env = getenv($key);
        return $env === false || $env === '' ? $default : $env;
    }
}
