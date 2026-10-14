<?php

declare(strict_types=1);

namespace ControlVet\Citas\Controller;

use Closure;
use ControlVet\Citas\Microservicio\CitaService;
use ControlVet\Citas\Validador\CitaValidador;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Http\Response;
use ControlVet\Kernel\Http\Router;
use ControlVet\Kernel\Security\AuthContext as A;
use ControlVet\Kernel\Security\Jwt;

/**
 * API REST del servicio citas (contrato en services/citas/README.md).
 */
final class CitaController
{
    /** @param Closure(A):CitaService $service Recibe el contexto para reenviar el JWT a otros servicios. */
    public function __construct(
        private readonly Closure $service,
        private readonly Jwt $jwt,
    ) {
    }

    public function register(Router $r): void
    {
        $todos = [A::ADMIN, A::RECEPCION, A::VETERINARIO, A::CLIENTE];
        $staff = [A::ADMIN, A::RECEPCION];

        $r->get('/citas', fn(Request $q) => $this->as($q, $todos, function (A $c, CitaService $s) use ($q) {
            CitaValidador::filtros($q->query);
            return Response::json($s->listar($c, $q->query));
        }));

        $r->get('/citas/proximas', fn(Request $q) => $this->as($q, $todos, function (A $c, CitaService $s) use ($q) {
            CitaValidador::filtros($q->query);
            $vet = $q->queryString('veterinario_id');
            $prop = $q->queryString('propietario_id');
            return Response::json($s->proximas($c, (int)($q->queryString('limite') ?: 5), $vet !== '' ? (int)$vet : null, $prop !== '' ? (int)$prop : null));
        }));

        $r->get('/citas/{id}', fn(Request $q) => $this->as($q, $todos, fn(A $c, CitaService $s) =>
            Response::json($s->ver($c, $q->intParam('id')))));

        $r->post('/citas', fn(Request $q) => $this->as($q, $staff, function (A $c, CitaService $s) use ($q) {
            CitaValidador::guardar($q->body);
            return Response::created($s->guardar($c, null, $q->body));
        }));

        $r->put('/citas/{id}', fn(Request $q) => $this->as($q, $staff, function (A $c, CitaService $s) use ($q) {
            CitaValidador::guardar($q->body);
            return Response::json($s->guardar($c, $q->intParam('id'), $q->body));
        }));

        $r->patch('/citas/{id}/estado', fn(Request $q) => $this->as($q, [A::ADMIN, A::RECEPCION, A::VETERINARIO], function (A $c, CitaService $s) use ($q) {
            CitaValidador::estado($q->body);
            return Response::json($s->cambiarEstado($c, $q->intParam('id'), (string)$q->body['estado']));
        }));
    }

    /** @param array<int,string> $roles */
    private function as(Request $q, array $roles, callable $action): Response
    {
        $ctx = A::fromRequest($q, $this->jwt)->requireRole(...$roles);
        return $action($ctx, ($this->service)($ctx));
    }
}
