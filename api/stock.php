<?php
/**
 * Ruta: controlvet/api/stock.php
 *
 * Endpoint AJAX autenticado EXCLUSIVAMENTE mediante JWT (Authorization:
 * Bearer <token>), independiente de la sesión PHP. Permite consultar
 * (GET) o actualizar (POST) la cantidad disponible de un producto desde
 * el panel de inventario mediante fetch(), demostrando el uso de JWT
 * como mecanismo de seguridad de API dentro del propio sistema.
 */

require_once __DIR__ . '/../config/jwt_config.php';
require_once __DIR__ . '/../core/JWT.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Producto.php';

header('Content-Type: application/json; charset=utf-8');

// Solo el rol administrador puede operar el inventario, validado por el claim "rol" del JWT.
$claims = Auth::requireApiToken(['administrador']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    $producto = Producto::find($id);
    echo json_encode($producto ?: ['error' => 'Producto no encontrado']);
    exit;
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($body['id'] ?? 0);
    $cantidad = (int)($body['cantidad_disponible'] ?? -1);

    if ($id <= 0 || $cantidad < 0) {
        http_response_code(422);
        echo json_encode(['error' => 'Datos inválidos']);
        exit;
    }

    Producto::actualizarCantidad($id, $cantidad);
    $producto = Producto::find($id);
    echo json_encode([
        'ok' => true,
        'producto' => $producto,
        'stock_bajo' => $producto['cantidad_disponible'] <= $producto['stock_minimo'],
        'autenticado_como' => $claims['email'],
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
