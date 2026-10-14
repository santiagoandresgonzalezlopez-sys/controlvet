<?php

declare(strict_types=1);

// Nunca mostrar errores internos al cliente (en XAMPP display_errors viene activado).
ini_set('display_errors', '0');

/**
 * Punto de entrada HTTP del microservicio clínica (historias y fórmulas).
 */

use ControlVet\Clinica\Controller\ClinicaController;
use ControlVet\Clinica\Microservicio\ClinicaService;
use ControlVet\Clinica\Microservicio\HttpDirectorioPacientes;
use ControlVet\Clinica\Microservicio\PdoHistoriaRepository;
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
$logger = (new Logger('clinica'))->withRequestId($request->requestId());
$app = new App('clinica', $logger, fn() => Db::fromEnv());

$service = fn(AuthContext $ctx) => new ClinicaService(
    new PdoHistoriaRepository($app->db()),
    new HttpDirectorioPacientes(
        new ServiceClient(Env::get('PACIENTES_URL', 'http://pacientes'), $ctx->token, $request->requestId(), $logger),
        $logger,
    ),
);
(new ClinicaController($service, Jwt::fromEnv()))->register($app->router());

$app->handle($request)->emit();
