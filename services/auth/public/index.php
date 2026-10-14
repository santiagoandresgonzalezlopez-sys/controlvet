<?php

declare(strict_types=1);

/**
 * Punto de entrada HTTP del microservicio auth.
 */

use ControlVet\Auth\Controller\AuthController;
use ControlVet\Auth\Microservicio\AuthService;
use ControlVet\Auth\Microservicio\PdoUsuarioRepository;
use ControlVet\Kernel\App;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Log\Logger;
use ControlVet\Kernel\Persistence\Db;
use ControlVet\Kernel\Security\Jwt;

require __DIR__ . '/../vendor/autoload.php';

$request = Request::fromGlobals();
$app = new App('auth', (new Logger('auth'))->withRequestId($request->requestId()), fn() => Db::fromEnv());
$jwt = Jwt::fromEnv();

$repo = fn() => new PdoUsuarioRepository($app->db());
(new AuthController(fn() => new AuthService($repo(), $jwt), $repo, $jwt))->register($app->router());

$app->handle($request)->emit();
