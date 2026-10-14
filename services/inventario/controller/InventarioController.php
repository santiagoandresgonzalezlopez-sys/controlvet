<?php

declare(strict_types=1);

namespace ControlVet\Inventario\Controller;

use Closure;
use ControlVet\Inventario\Microservicio\InventarioService;
use ControlVet\Inventario\Microservicio\ProductoRepository;
use ControlVet\Inventario\Validador\ProductoValidador;
use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Http\Response;
use ControlVet\Kernel\Http\Router;
use ControlVet\Kernel\Security\AuthContext as A;
use ControlVet\Kernel\Security\Jwt;

/**
 * API REST del servicio inventario. Uso exclusivo del rol administrador.
 */
final class InventarioController
{
    /**
     * @param Closure():InventarioService  $service
     * @param Closure():ProductoRepository $repo
     */
    public function __construct(
        private readonly Closure $service,
        private readonly Closure $repo,
        private readonly Jwt $jwt,
    ) {
    }

    public function register(Router $r): void
    {
        $r->get('/productos', fn(Request $q) => $this->admin($q, fn() =>
            Response::json(($this->repo)()->activos($q->queryString('buscar')))));

        $r->get('/productos/stock-bajo', fn(Request $q) => $this->admin($q, fn() =>
            Response::json(($this->repo)()->stockBajo())));

        $r->get('/productos/{id}', fn(Request $q) => $this->admin($q, fn() =>
            Response::json(($this->repo)()->find($q->intParam('id')) ?? throw HttpException::notFound('Producto no encontrado'))));

        $r->post('/productos', fn(Request $q) => $this->admin($q, function () use ($q) {
            ProductoValidador::guardar($q->body, true);
            return Response::created(($this->service)()->guardar(null, $q->body));
        }));

        $r->put('/productos/{id}', fn(Request $q) => $this->admin($q, function () use ($q) {
            ProductoValidador::guardar($q->body, false);
            return Response::json(($this->service)()->guardar($q->intParam('id'), $q->body));
        }));

        $r->patch('/productos/{id}/stock', fn(Request $q) => $this->admin($q, function () use ($q) {
            ProductoValidador::stock($q->body);
            return Response::json(($this->service)()->actualizarStock($q->intParam('id'), (int)$q->body['cantidad_disponible']));
        }));

        $r->patch('/productos/{id}/desactivar', fn(Request $q) => $this->admin($q, fn() =>
            Response::json(($this->service)()->desactivar($q->intParam('id')))));

        $r->get('/resumen', fn(Request $q) => $this->admin($q, fn() => Response::json(($this->service)()->resumen())));
    }

    private function admin(Request $q, callable $action): Response
    {
        A::fromRequest($q, $this->jwt)->requireRole(A::ADMIN);
        return $action();
    }
}
