<?php

declare(strict_types=1);

// Nunca mostrar errores internos al cliente (en XAMPP display_errors viene activado).
ini_set('display_errors', '0');

/**
 * Punto de entrada HTTP del microservicio citas.
 */

use ControlVet\Citas\Controller\CitaController;
use ControlVet\Citas\Microservicio\CitaService;
use ControlVet\Citas\Microservicio\HttpDirectorioClinico;
use ControlVet\Citas\Microservicio\PdoCitaRepository;
use ControlVet\Kernel\App;
use ControlVet\Kernel\Config\Env;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Http\ServiceClient;
use ControlVet\Kernel\Log\Logger;
use ControlVet\Kernel\Persistence\Db;
use ControlVet\Kernel\Security\AuthContext;
use ControlVet\Kernel\Security\Jwt;

require __DIR__ . '/../vendor/autoload.php';

// Configuración propia del servicio (XAMPP): config/servicio.php, no versionado.
Env::load(__DIR__ . '/../config/servicio.php');

$request = Request::fromGlobals();
$logger = (new Logger('citas'))->withRequestId($request->requestId());
$app = new App('citas', $logger, fn() => Db::fromEnv());

$service = function (AuthContext $ctx) use ($app, $request, $logger): CitaService {
    $client = fn(string $env, string $default) => new ServiceClient(Env::get($env, $default), $ctx->token, $request->requestId(), $logger);
    return new CitaService(
        new PdoCitaRepository($app->db()),
        new HttpDirectorioClinico($client('PACIENTES_URL', 'http://pacientes'), $client('AUTH_URL', 'http://auth')),
    );
};
(new CitaController($service, Jwt::fromEnv()))->register($app->router());

$app->handle($request)->emit();
