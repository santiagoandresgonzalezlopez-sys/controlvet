<?php

declare(strict_types=1);

/**
 * Punto de entrada HTTP del microservicio inventario.
 */

use ControlVet\Inventario\Controller\InventarioController;
use ControlVet\Inventario\Microservicio\InventarioService;
use ControlVet\Inventario\Microservicio\PdoProductoRepository;
use ControlVet\Kernel\App;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Log\Logger;
use ControlVet\Kernel\Persistence\Db;
use ControlVet\Kernel\Security\Jwt;

require __DIR__ . '/../vendor/autoload.php';

$request = Request::fromGlobals();
$app = new App('inventario', (new Logger('inventario'))->withRequestId($request->requestId()), fn() => Db::fromEnv());

$repo = fn() => new PdoProductoRepository($app->db());
(new InventarioController(fn() => new InventarioService($repo()), $repo, Jwt::fromEnv()))->register($app->router());

$app->handle($request)->emit();
