<?php

declare(strict_types=1);

/**
 * Punto de entrada HTTP del microservicio citas.
 */

use ControlVet\Citas\Controller\CitaController;
use ControlVet\Citas\Microservicio\CitaService;
use ControlVet\Citas\Microservicio\HttpDirectorioClinico;
use ControlVet\Citas\Microservicio\PdoCitaRepository;
use ControlVet\Kernel\App;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Http\ServiceClient;
use ControlVet\Kernel\Log\Logger;
use ControlVet\Kernel\Persistence\Db;
use ControlVet\Kernel\Security\AuthContext;
use ControlVet\Kernel\Security\Jwt;

require __DIR__ . '/../vendor/autoload.php';

$request = Request::fromGlobals();
$logger = (new Logger('citas'))->withRequestId($request->requestId());
$app = new App('citas', $logger, fn() => Db::fromEnv());

$service = function (AuthContext $ctx) use ($app, $request, $logger): CitaService {
    $client = fn(string $env, string $default) => new ServiceClient((string)(getenv($env) ?: $default), $ctx->token, $request->requestId(), $logger);
    return new CitaService(
        new PdoCitaRepository($app->db()),
        new HttpDirectorioClinico($client('PACIENTES_URL', 'http://pacientes'), $client('AUTH_URL', 'http://auth')),
    );
};
(new CitaController($service, Jwt::fromEnv()))->register($app->router());

$app->handle($request)->emit();
