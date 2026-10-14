<?php
ini_set('display_errors', '0');
/**
 * Ruta: controlvet/web/health.php
 *
 * Comprobación de salud del frontend para Ansible y Nagios. Verifica que el
 * proceso responde y que alcanza el /health de cada microservicio. No
 * requiere sesión y no expone datos de negocio.
 *   200 {"status":"ok"}        todo disponible
 *   503 {"status":"degraded"}  algún servicio no responde
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$servicios = require __DIR__ . '/config/services.php'; // incluye config/env.php
$estado = [];
foreach ($servicios as $nombre => $url) {
    $ctx = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
    $raw = @file_get_contents(rtrim($url, '/') . '/health', false, $ctx);
    $json = $raw !== false ? json_decode($raw, true) : null;
    $estado[$nombre] = ($json['status'] ?? null) === 'ok' ? 'ok' : 'error';
}

$ok = !in_array('error', $estado, true);
http_response_code($ok ? 200 : 503);
echo json_encode([
    'status' => $ok ? 'ok' : 'degraded',
    'service' => 'web',
    'version' => cv_env('APP_VERSION', 'dev'),
    'dependencies' => $estado,
    'time' => gmdate('c'),
]);
