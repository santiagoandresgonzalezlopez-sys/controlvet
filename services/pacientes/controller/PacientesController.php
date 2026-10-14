<?php

declare(strict_types=1);

namespace ControlVet\Pacientes\Controller;

use Closure;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Http\Response;
use ControlVet\Kernel\Http\Router;
use ControlVet\Kernel\Security\AuthContext as A;
use ControlVet\Kernel\Security\Jwt;
use ControlVet\Pacientes\Microservicio\PacientesRepository;
use ControlVet\Pacientes\Microservicio\PacientesService;
use ControlVet\Pacientes\Validador\PacientesValidador;

/**
 * API REST del servicio pacientes (contrato en services/pacientes/README.md).
 */
final class PacientesController
{
    /**
     * @param Closure():PacientesService    $service
     * @param Closure():PacientesRepository $repo
     */
    public function __construct(
        private readonly Closure $service,
        private readonly Closure $repo,
        private readonly Jwt $jwt,
    ) {
    }

    public function register(Router $r): void
    {
        $staff = [A::ADMIN, A::RECEPCION];
        $todos = [A::ADMIN, A::RECEPCION, A::VETERINARIO, A::CLIENTE];

        // ------------------------------------------------------ Propietarios
        $r->get('/propietarios', fn(Request $q) => $this->as($q, $staff, fn() =>
            Response::json(($this->repo)()->propietarios($q->queryString('buscar')))));

        $r->get('/propietarios/existe-documento', fn(Request $q) => $this->as($q, $staff, fn() =>
            Response::json(['existe' => ($this->repo)()->documentoExists($q->queryString('documento'), (int)$q->queryString('excluir') ?: null)])));

        $r->get('/propietarios/{id}', fn(Request $q) => $this->as($q, $todos, fn(A $c) =>
            Response::json(($this->service)()->propietario($c, $q->intParam('id')))));

        $r->get('/propietarios/{id}/mascotas', fn(Request $q) => $this->as($q, $todos, function (A $c) use ($q) {
            ($this->service)()->propietario($c, $q->intParam('id'));
            return Response::json(($this->service)()->mascotas($c, '', $q->intParam('id'), []));
        }));

        $r->post('/propietarios', fn(Request $q) => $this->as($q, $staff, function () use ($q) {
            PacientesValidador::propietario($q->body);
            return Response::created(($this->service)()->guardarPropietario(null, $q->body));
        }));

        $r->put('/propietarios/{id}', fn(Request $q) => $this->as($q, $staff, function () use ($q) {
            PacientesValidador::propietario($q->body);
            return Response::json(($this->service)()->guardarPropietario($q->intParam('id'), $q->body));
        }));

        $r->patch('/propietarios/{id}/desactivar', fn(Request $q) => $this->as($q, $staff, fn() =>
            Response::json(($this->service)()->desactivarPropietario($q->intParam('id')))));

        // ---------------------------------------------------------- Mascotas
        $r->get('/mascotas', fn(Request $q) => $this->as($q, $todos, function (A $c) use ($q) {
            $ids = array_values(array_filter(array_map('intval', explode(',', $q->queryString('ids'))), fn($i) => $i > 0));
            $prop = $q->queryString('propietario_id');
            return Response::json(($this->service)()->mascotas($c, $q->queryString('buscar'), $prop !== '' ? (int)$prop : null, $ids));
        }));

        $r->get('/mascotas/{id}', fn(Request $q) => $this->as($q, $todos, fn(A $c) =>
            Response::json(($this->service)()->mascota($c, $q->intParam('id')))));

        $r->post('/mascotas', fn(Request $q) => $this->as($q, $staff, function () use ($q) {
            PacientesValidador::mascota($q->body);
            return Response::created(($this->service)()->guardarMascota(null, $q->body));
        }));

        $r->put('/mascotas/{id}', fn(Request $q) => $this->as($q, $staff, function () use ($q) {
            PacientesValidador::mascota($q->body);
            return Response::json(($this->service)()->guardarMascota($q->intParam('id'), $q->body));
        }));

        $r->patch('/mascotas/{id}/peso', fn(Request $q) => $this->as($q, [A::VETERINARIO], function () use ($q) {
            PacientesValidador::peso($q->body);
            return Response::json(($this->service)()->actualizarPeso($q->intParam('id'), (float)$q->body['peso']));
        }));

        $r->get('/resumen', fn(Request $q) => $this->as($q, $staff, fn() => Response::json(($this->service)()->resumen())));
    }

    /** @param array<int,string> $roles */
    private function as(Request $q, array $roles, callable $action): Response
    {
        return $action(A::fromRequest($q, $this->jwt)->requireRole(...$roles));
    }
}
