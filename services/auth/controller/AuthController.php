<?php

declare(strict_types=1);

namespace ControlVet\Auth\Controller;

use ControlVet\Auth\Microservicio\AuthService;
use ControlVet\Auth\Microservicio\UsuarioRepository;
use ControlVet\Auth\Validador\UsuarioValidador;
use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Http\Response;
use ControlVet\Kernel\Http\Router;
use ControlVet\Kernel\Security\AuthContext;
use ControlVet\Kernel\Security\Jwt;

/**
 * API REST del servicio de autenticación (contrato en services/auth/README.md).
 */
final class AuthController
{
    public function __construct(
        private readonly \Closure $service,     // fn(): AuthService
        private readonly \Closure $repo,        // fn(): UsuarioRepository
        private readonly Jwt $jwt,
    ) {
    }

    public function register(Router $r): void
    {
        $r->post('/auth/login', fn(Request $q) => $this->login($q));
        $r->get('/auth/me', fn(Request $q) => $this->me($q));

        $r->get('/roles', fn(Request $q) => $this->admin($q, fn() => Response::json($this->repo()->roles())));
        $r->get('/usuarios', fn(Request $q) => $this->admin($q, fn() => Response::json($this->repo()->all())));
        $r->get('/usuarios/existe-email', fn(Request $q) => $this->admin($q, fn() => Response::json([
            'existe' => $this->repo()->emailExists($q->queryString('email'), (int)$q->queryString('excluir') ?: null),
        ])));
        $r->get('/usuarios/{id}', fn(Request $q) => $this->admin($q, fn() => Response::json(
            $this->repo()->findById($q->intParam('id')) ?? throw HttpException::notFound('Usuario no encontrado')
        )));
        $r->post('/usuarios', fn(Request $q) => $this->admin($q, function () use ($q) {
            UsuarioValidador::guardar($q->body, true);
            return Response::created($this->service()->crear($q->body));
        }));
        $r->put('/usuarios/{id}', fn(Request $q) => $this->admin($q, function () use ($q) {
            UsuarioValidador::guardar($q->body, false);
            return Response::json($this->service()->actualizar($q->intParam('id'), $q->body));
        }));
        $r->patch('/usuarios/{id}/estado', fn(Request $q) => $this->admin($q, function (AuthContext $ctx) use ($q) {
            UsuarioValidador::estado($q->body);
            return Response::json($this->service()->cambiarEstado($q->intParam('id'), (string)$q->body['estado'], $ctx->userId()));
        }));

        // Consulta de veterinarios: disponible para cualquier usuario autenticado
        // (agenda de citas, comprobantes, historias) y para otros servicios.
        $r->get('/veterinarios', function (Request $q) {
            AuthContext::fromRequest($q, $this->jwt);
            return Response::json($this->repo()->veterinarios($q->queryString('todos') === '1'));
        });
        $r->get('/veterinarios/{id}', function (Request $q) {
            AuthContext::fromRequest($q, $this->jwt);
            foreach ($this->repo()->veterinarios(true) as $vet) {
                if ((int)$vet['id'] === $q->intParam('id')) {
                    return Response::json($vet);
                }
            }
            throw HttpException::notFound('Veterinario no encontrado');
        });
    }

    private function login(Request $q): Response
    {
        UsuarioValidador::login($q->body);
        return Response::json($this->service()->login((string)$q->body['email'], (string)$q->body['password']));
    }

    private function me(Request $q): Response
    {
        $ctx = AuthContext::fromRequest($q, $this->jwt);
        $usuario = $this->repo()->findById($ctx->userId());
        if (!$usuario || $usuario['estado'] !== 'activo') {
            throw HttpException::unauthorized('Usuario inactivo o inexistente');
        }
        return Response::json($usuario);
    }

    private function admin(Request $q, callable $action): Response
    {
        $ctx = AuthContext::fromRequest($q, $this->jwt)->requireRole(AuthContext::ADMIN);
        return $action($ctx);
    }

    private function service(): AuthService
    {
        return ($this->service)();
    }

    private function repo(): UsuarioRepository
    {
        return ($this->repo)();
    }
}
