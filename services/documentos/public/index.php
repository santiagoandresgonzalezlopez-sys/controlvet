<?php

declare(strict_types=1);

/**
 * Punto de entrada HTTP del microservicio documentos (PDF).
 */

use ControlVet\Documentos\Controller\DocumentoController;
use ControlVet\Documentos\Microservicio\DocumentoService;
use ControlVet\Documentos\Microservicio\HttpFuentesDocumentales;
use ControlVet\Documentos\Microservicio\PdoDocumentoRepository;
use ControlVet\Kernel\App;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Http\ServiceClient;
use ControlVet\Kernel\Log\Logger;
use ControlVet\Kernel\Persistence\Db;
use ControlVet\Kernel\Security\AuthContext;
use ControlVet\Kernel\Security\Jwt;

require __DIR__ . '/../vendor/autoload.php';

$request = Request::fromGlobals();
$logger = (new Logger('documentos'))->withRequestId($request->requestId());
$app = new App('documentos', $logger, fn() => Db::fromEnv());

$service = function (AuthContext $ctx) use ($app, $request, $logger): DocumentoService {
    $client = fn(string $env, string $default) => new ServiceClient((string)(getenv($env) ?: $default), $ctx->token, $request->requestId(), $logger);
    return new DocumentoService(
        new PdoDocumentoRepository($app->db()),
        new HttpFuentesDocumentales(
            $client('CITAS_URL', 'http://citas'),
            $client('CLINICA_URL', 'http://clinica'),
            $client('PACIENTES_URL', 'http://pacientes'),
            $client('AUTH_URL', 'http://auth'),
        ),
    );
};
(new DocumentoController($service, Jwt::fromEnv()))->register($app->router());

$app->handle($request)->emit();
