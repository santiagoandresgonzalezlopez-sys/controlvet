<?php

declare(strict_types=1);

namespace ControlVet\Documentos\Controller;

use Closure;
use ControlVet\Documentos\Microservicio\DocumentoService;
use ControlVet\Documentos\Validador\DocumentoValidador;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Http\Response;
use ControlVet\Kernel\Http\Router;
use ControlVet\Kernel\Security\AuthContext as A;
use ControlVet\Kernel\Security\Jwt;

/**
 * API REST del servicio documentos (contrato en services/documentos/README.md).
 */
final class DocumentoController
{
    /** @param Closure(A):DocumentoService $service */
    public function __construct(
        private readonly Closure $service,
        private readonly Jwt $jwt,
    ) {
    }

    public function register(Router $r): void
    {
        $r->get('/documentos', fn(Request $q) => $this->as($q, fn(A $c, DocumentoService $s) => Response::json($s->listar($c))));

        $generadores = [
            '/documentos/comprobante-cita/{id}' => 'comprobanteCita',
            '/documentos/resumen-consulta/{id}' => 'resumenConsulta',
            '/documentos/formula-medica/{id}' => 'formulaMedica',
        ];
        foreach ($generadores as $ruta => $metodo) {
            $r->post($ruta, fn(Request $q) => $this->as($q, function (A $c, DocumentoService $s) use ($q, $metodo) {
                DocumentoValidador::referencia($q->intParam('id'));
                [$nombre, $pdf] = $s->{$metodo}($c, $q->intParam('id'));
                return Response::pdf($pdf, $nombre);
            }));
        }
    }

    private function as(Request $q, callable $action): Response
    {
        $ctx = A::fromRequest($q, $this->jwt)->requireRole(A::ADMIN, A::RECEPCION, A::VETERINARIO, A::CLIENTE);
        return $action($ctx, ($this->service)($ctx));
    }
}
