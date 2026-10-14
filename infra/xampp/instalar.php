<?php
/**
 * ControlVet · instalación de los microservicios en XAMPP (sin Docker).
 *
 *   powershell -File infra\xampp\bd.ps1 iniciar      (instancia MariaDB dedicada en 127.0.0.1:3307)
 *   C:\xampp\php\php.exe infra\xampp\instalar.php [--root-user=root] [--root-pass=...] [--db-host=127.0.0.1]
 *                                                 [--db-port=3307] [--base-url=http://localhost/controlvet]
 *
 * Por seguridad se niega a ejecutarse contra el puerto 3306 (MySQL general de
 * XAMPP, compartido con otros proyectos) salvo con --permitir-3306.
 *
 * Idempotente. Por cada microservicio:
 *   1. crea el esquema cv_<servicio> y el usuario <servicio>_svc con privilegios
 *      SELECT/INSERT/UPDATE/DELETE solo sobre las tablas de su esquema
 *      (concedidos por tabla: no dependen de mysql.db, que en algunas
 *      instalaciones de XAMPP queda dañada tras un cierre inesperado);
 *   2. aplica sus scripts services/<servicio>/database/*.sql;
 *   3. genera services/<servicio>/config/servicio.env (no versionado) con su
 *      conexión, el prefijo de sus endpoints y las URL de los servicios de los que depende.
 * Además crea el usuario de monitoreo nagios_mon (solo USAGE) y web/config/web.env.
 * Las contraseñas existentes se conservan; nunca se imprimen.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function leerEnv(string $archivo): array
{
    $vars = [];
    foreach (is_file($archivo) ? file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [] as $l) {
        if ($l !== '' && $l[0] !== '#' && str_contains($l, '=')) {
            [$k, $v] = explode('=', $l, 2);
            $vars[trim($k)] = trim($v);
        }
    }
    return $vars;
}

$raiz = dirname(__DIR__, 2);

$opts = getopt('', ['root-user::', 'root-pass::', 'db-host::', 'db-port::', 'base-url::', 'app-env::', 'permitir-3306']);
$rootUser = $opts['root-user'] ?? 'root';
$rootPass = $opts['root-pass'] ?? (leerEnv(__DIR__ . '/bd-root.env')['DB_ROOT_PASSWORD'] ?? '');
$dbHost = $opts['db-host'] ?? '127.0.0.1';
$dbPort = $opts['db-port'] ?? '3307';
if ($dbPort === '3306' && !isset($opts['permitir-3306'])) {
    fwrite(STDERR, "Puerto 3306 = MySQL general de XAMPP. Use la instancia dedicada (bd.ps1, 3307) o --permitir-3306.\n");
    exit(2);
}
$baseUrl = rtrim($opts['base-url'] ?? 'http://localhost/controlvet', '/');
$appEnv = $opts['app-env'] ?? 'dev';
$version = trim((string)@file_get_contents($raiz . '/VERSION')) ?: 'dev';

/** Dependencias HTTP de cada servicio (variable => servicio). */
$servicios = [
    'auth' => [],
    'pacientes' => [],
    'citas' => ['PACIENTES_URL' => 'pacientes', 'AUTH_URL' => 'auth'],
    'clinica' => ['PACIENTES_URL' => 'pacientes'],
    'inventario' => [],
    'documentos' => ['CITAS_URL' => 'citas', 'CLINICA_URL' => 'clinica', 'PACIENTES_URL' => 'pacientes', 'AUTH_URL' => 'auth'],
];

function escribirEnv(string $archivo, string $titulo, array $vars): void
{
    @mkdir(dirname($archivo), 0750, true);
    $txt = "# $titulo\n# Generado por infra/xampp/instalar.php el " . date('c') . ". NO versionar.\n";
    foreach ($vars as $k => $v) {
        $txt .= "$k=$v\n";
    }
    file_put_contents($archivo, $txt);
    @chmod($archivo, 0600);
}

function secreto(int $bytes = 24): string
{
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', 'Ab'), '=');
}

function paso(string $msg): void
{
    fwrite(STDOUT, "[controlvet] $msg\n");
}

try {
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;charset=utf8mb4", $rootUser, $rootPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "No fue posible conectar a MySQL de XAMPP ($dbHost:$dbPort). ¿Está iniciado? " . $e->getMessage() . "\n");
    exit(1);
}
paso('Conectado a ' . $pdo->query('SELECT VERSION()')->fetchColumn());

$webEnvFile = $raiz . '/web/config/web.env';
$jwt = leerEnv($webEnvFile)['JWT_SECRET'] ?? secreto(36);
$hosts = ['localhost', '127.0.0.1'];

foreach ($servicios as $svc => $dependencias) {
    $envFile = "$raiz/services/$svc/config/servicio.env";
    $pass = leerEnv($envFile)['DB_PASSWORD'] ?? secreto();
    $esquema = "cv_$svc";
    $usuario = "{$svc}_svc";

    paso("Servicio $svc: esquema $esquema, usuario $usuario");
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$esquema` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    foreach ($hosts as $h) {
        $pdo->exec("CREATE USER IF NOT EXISTS '$usuario'@'$h' IDENTIFIED BY " . $pdo->quote($pass));
        $pdo->exec("ALTER USER '$usuario'@'$h' IDENTIFIED BY " . $pdo->quote($pass));
    }

    $pdo->exec("USE `$esquema`");
    foreach (glob("$raiz/services/$svc/database/*.sql") as $sql) {
        $pdo->exec((string)file_get_contents($sql));
        paso('  aplicado ' . basename($sql));
    }

    $tablas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tablas as $tabla) {
        foreach ($hosts as $h) {
            $pdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON `$esquema`.`$tabla` TO '$usuario'@'$h'");
        }
    }
    paso('  privilegios DML sobre ' . implode(', ', $tablas));

    $vars = [
        'APP_ENV' => $appEnv,
        'APP_VERSION' => $version,
        'APP_BASE_PATH' => parse_url($baseUrl, PHP_URL_PATH) . "/api/$svc",
        'DB_HOST' => $dbHost,
        'DB_PORT' => $dbPort,
        'DB_NAME' => $esquema,
        'DB_USER' => $usuario,
        'DB_PASSWORD' => $pass,
        'JWT_SECRET' => $jwt,
        'JWT_ISSUER' => 'ControlVet',
    ];
    foreach ($dependencias as $var => $dep) {
        $vars[$var] = "$baseUrl/api/$dep";
    }
    escribirEnv($envFile, "Configuración del microservicio $svc", $vars);
    paso("  generado services/$svc/config/servicio.env");
}

$monitorFile = $raiz . '/infra/xampp/monitor.env';
$monitorPass = leerEnv($monitorFile)['MONITOR_DB_PASSWORD'] ?? secreto();
foreach ($hosts as $h) {
    $pdo->exec("CREATE USER IF NOT EXISTS 'nagios_mon'@'$h' IDENTIFIED BY " . $pdo->quote($monitorPass));
    $pdo->exec("ALTER USER 'nagios_mon'@'$h' IDENTIFIED BY " . $pdo->quote($monitorPass));
}
escribirEnv($monitorFile, 'Usuario de monitoreo (solo USAGE)', ['MONITOR_DB_PASSWORD' => $monitorPass]);
paso('Usuario de monitoreo nagios_mon (solo USAGE)');

$web = ['APP_ENV' => $appEnv, 'APP_VERSION' => $version, 'JWT_SECRET' => $jwt, 'JWT_ISSUER' => 'ControlVet'];
foreach (array_keys($servicios) as $svc) {
    $web[strtoupper($svc) . '_URL'] = "$baseUrl/api/$svc";
}
escribirEnv($webEnvFile, 'Configuración del frontend web', $web);
paso('Generado web/config/web.env');

paso("Listo. Frontend: $baseUrl/web/ · Endpoints: $baseUrl/api/<servicio>/... (ver docs/endpoints.md)");
