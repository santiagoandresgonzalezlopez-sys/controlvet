<?php

declare(strict_types=1);

// Nunca mostrar errores internos al cliente (en XAMPP display_errors viene activado).
ini_set('display_errors', '0');

/**
 * Punto de entrada HTTP del microservicio inventario.
 */

use ControlVet\Inventario\Controller\InventarioController;
use ControlVet\Inventario\Microservicio\InventarioService;
use ControlVet\Inventario\Microservicio\PdoProductoRepository;
use ControlVet\Kernel\App;
use ControlVet\Kernel\Config\Env;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Log\Logger;
use ControlVet\Kernel\Persistence\Db;
use ControlVet\Kernel\Security\Jwt;

require __DIR__ . '/../vendor/autoload.php';

// Configuración propia del servicio (XAMPP): config/servicio.env, no versionado.
Env::load(__DIR__ . '/../config/servicio.env');

$request = Request::fromGlobals();
$app = new App('inventario', (new Logger('inventario'))->withRequestId($request->requestId()), fn() => Db::fromEnv());

$repo = fn() => new PdoProductoRepository($app->db());
(new InventarioController(fn() => new InventarioService($repo()), $repo, Jwt::fromEnv()))->register($app->router());

$app->handle($request)->emit();
