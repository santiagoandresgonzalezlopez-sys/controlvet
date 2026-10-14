<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Tests;

use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Security\AuthContext;
use ControlVet\Kernel\Security\Jwt;
use PHPUnit\Framework\TestCase;

final class AuthContextTest extends TestCase
{
    private Jwt $jwt;

    protected function setUp(): void
    {
        $this->jwt = new Jwt(str_repeat('k', 40));
    }

    /** @param array<string,mixed> $claims */
    private function ctx(array $claims): AuthContext
    {
        $req = new Request('GET', '/', headers: ['authorization' => 'Bearer ' . $this->jwt->encode($claims)]);
        return AuthContext::fromRequest($req, $this->jwt);
    }

    public function testSinTokenRetorna401(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(401);
        AuthContext::fromRequest(new Request('GET', '/'), $this->jwt);
    }

    public function testRolNoPermitidoRetorna403(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(403);
        $this->ctx(['sub' => 4, 'rol' => 'cliente', 'propietario_id' => 1])->requireRole(AuthContext::ADMIN);
    }

    public function testClienteNoAccedeARecursosAjenos(): void
    {
        $this->expectExceptionCode(403);
        $this->ctx(['sub' => 4, 'rol' => 'cliente', 'propietario_id' => 1])->requireOwnership(2);
    }

    public function testClienteAccedeASusRecursos(): void
    {
        $this->ctx(['sub' => 4, 'rol' => 'cliente', 'propietario_id' => 1])->requireOwnership(1);
        $this->addToAssertionCount(1);
    }

    public function testClienteSinPropietarioNoAccedeANada(): void
    {
        $this->expectExceptionCode(403);
        $this->ctx(['sub' => 9, 'rol' => 'cliente', 'propietario_id' => null])->requireOwnership(null);
    }

    public function testPersonalNoEstaRestringidoPorPropietario(): void
    {
        $this->ctx(['sub' => 3, 'rol' => 'veterinario'])->requireOwnership(2);
        $this->addToAssertionCount(1);
    }
}
