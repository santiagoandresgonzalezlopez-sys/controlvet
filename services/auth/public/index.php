<?php

declare(strict_types=1);

// Nunca mostrar errores internos al cliente (en XAMPP display_errors viene activado).
ini_set('display_errors', '0');

/**
 * Punto de entrada HTTP del microservicio auth.
 */

use ControlVet\Auth\Controller\AuthController;
use ControlVet\Auth\Microservicio\AuthService;
use ControlVet\Auth\Microservicio\PdoUsuarioRepository;
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
$app = new App('auth', (new Logger('auth'))->withRequestId($request->requestId()), fn() => Db::fromEnv());
$jwt = Jwt::fromEnv();

$repo = fn() => new PdoUsuarioRepository($app->db());
(new AuthController(fn() => new AuthService($repo(), $jwt), $repo, $jwt))->register($app->router());

$app->handle($request)->emit();
