<?php

declare(strict_types=1);

/**
 * Punto de entrada HTTP del microservicio pacientes (propietarios y mascotas).
 */

use ControlVet\Kernel\App;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Log\Logger;
use ControlVet\Kernel\Persistence\Db;
use ControlVet\Kernel\Security\Jwt;
use ControlVet\Pacientes\Controller\PacientesController;
use ControlVet\Pacientes\Microservicio\PacientesService;
use ControlVet\Pacientes\Microservicio\PdoPacientesRepository;

require __DIR__ . '/../vendor/autoload.php';

$request = Request::fromGlobals();
$app = new App('pacientes', (new Logger('pacientes'))->withRequestId($request->requestId()), fn() => Db::fromEnv());

$repo = fn() => new PdoPacientesRepository($app->db());
(new PacientesController(fn() => new PacientesService($repo()), $repo, Jwt::fromEnv()))->register($app->router());

$app->handle($request)->emit();
