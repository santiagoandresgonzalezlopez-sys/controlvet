<?php
ini_set('display_errors', '0');
/**
 * Ruta: controlvet/web/api/stock.php
 *
 * Endpoint AJAX autenticado EXCLUSIVAMENTE mediante JWT (Authorization:
 * Bearer <token> o cookie HttpOnly), independiente de la sesión PHP.
 * Permite consultar (GET) o actualizar (POST) la cantidad disponible de un
 * producto desde el panel de inventario mediante fetch().
 *
 * Desde v1.1.0 actúa como pasarela hacia el microservicio inventario: el
 * mismo JWT se reenvía y el servicio vuelve a validar el rol administrador.
 */

require_once __DIR__ . '/../config/jwt_config.php';
require_once __DIR__ . '/../core/JWT.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Api.php';

header('Content-Type: application/json; charset=utf-8');

// Solo el rol administrador puede operar el inventario, validado por el claim "rol" del JWT.
$claims = Auth::requireApiToken(['administrador']);

function responder(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

function inventario(string $method, string $path, ?array $body, string $token): array
{
    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => "Accept: application/json\r\nContent-Type: application/json\r\nAuthorization: Bearer $token",
        'content' => $body !== null ? json_encode($body) : '',
        'timeout' => 5,
        'ignore_errors' => true,
    ]]);
    $raw = @file_get_contents(Api::baseUrl('inventario') . $path, false, $context);
    preg_match('#^HTTP/\S+\s+(\d{3})#', $http_response_header[0] ?? '', $m);
    if ($raw === false || !isset($m[1])) {
        responder(503, ['error' => 'Servicio de inventario no disponible']);
    }
    return [(int)$m[1], json_decode($raw, true) ?? []];
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    [$status, $data] = inventario('GET', '/productos/' . (int)($_GET['id'] ?? 0), null, $claims['_token']);
    responder($status, $status === 404 ? ['error' => 'Producto no encontrado'] : $data);
}

if ($method === 'POST') {
    // Solo JSON: impide que un formulario de otro sitio (text/plain) llegue aquí.
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
        responder(415, ['error' => 'Se requiere Content-Type: application/json']);
    }
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($body['id'] ?? 0);
    $cantidad = (int)($body['cantidad_disponible'] ?? -1);

    if ($id <= 0 || $cantidad < 0) {
        responder(422, ['error' => 'Datos inválidos']);
    }

    [$status, $producto] = inventario('PATCH', '/productos/' . $id . '/stock', ['cantidad_disponible' => $cantidad], $claims['_token']);
    if ($status !== 200) {
        responder($status, ['error' => $producto['error'] ?? 'No fue posible actualizar el stock']);
    }
    responder(200, [
        'ok' => true,
        'producto' => $producto,
        'stock_bajo' => $producto['stock_bajo'],
        'autenticado_como' => $claims['email'],
    ]);
}

responder(405, ['error' => 'Método no permitido']);
