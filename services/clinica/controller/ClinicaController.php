<?php

declare(strict_types=1);

namespace ControlVet\Clinica\Controller;

use Closure;
use ControlVet\Clinica\Microservicio\ClinicaService;
use ControlVet\Clinica\Validador\ClinicaValidador;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Http\Response;
use ControlVet\Kernel\Http\Router;
use ControlVet\Kernel\Security\AuthContext as A;
use ControlVet\Kernel\Security\Jwt;

/**
 * API REST del servicio clínica (contrato en services/clinica/README.md).
 */
final class ClinicaController
{
    /** @param Closure(A):ClinicaService $service */
    public function __construct(
        private readonly Closure $service,
        private readonly Jwt $jwt,
    ) {
    }

    public function register(Router $r): void
    {
        $todos = [A::ADMIN, A::RECEPCION, A::VETERINARIO, A::CLIENTE];

        $r->get('/historias', fn(Request $q) => $this->as($q, $todos, function (A $c, ClinicaService $s) use ($q) {
            ClinicaValidador::filtros($q->query);
            return Response::json($s->listar($c, $q->query, (int)($q->queryString('limite') ?: 500)));
        }));

        $r->get('/historias/{id}', fn(Request $q) => $this->as($q, $todos, fn(A $c, ClinicaService $s) =>
            Response::json($s->ver($c, $q->intParam('id')))));

        $r->post('/historias', fn(Request $q) => $this->as($q, [A::VETERINARIO], function (A $c, ClinicaService $s) use ($q) {
            ClinicaValidador::historia($q->body);
            return Response::created($s->registrar($c, $q->body));
        }));

        $r->get('/historias/{id}/formulas', fn(Request $q) => $this->as($q, $todos, fn(A $c, ClinicaService $s) =>
            Response::json($s->formulas($c, $q->intParam('id')))));

        $r->post('/historias/{id}/formulas', fn(Request $q) => $this->as($q, [A::VETERINARIO], function (A $c, ClinicaService $s) use ($q) {
            ClinicaValidador::formula($q->body);
            return Response::created($s->crearFormula($c, $q->intParam('id'), $q->body));
        }));

        $r->get('/formulas/{id}', fn(Request $q) => $this->as($q, $todos, fn(A $c, ClinicaService $s) =>
            Response::json($s->formula($c, $q->intParam('id')))));
    }

    /** @param array<int,string> $roles */
    private function as(Request $q, array $roles, callable $action): Response
    {
        $ctx = A::fromRequest($q, $this->jwt)->requireRole(...$roles);
        return $action($ctx, ($this->service)($ctx));
    }
}
