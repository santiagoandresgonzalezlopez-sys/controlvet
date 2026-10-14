<?php

declare(strict_types=1);

namespace ControlVet\Auth\Tests\Unit;

use ControlVet\Auth\Microservicio\AuthService;
use ControlVet\Auth\Tests\InMemoryUsuarioRepository;
use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Security\Jwt;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    private InMemoryUsuarioRepository $repo;
    private Jwt $jwt;
    private AuthService $service;

    protected function setUp(): void
    {
        $this->repo = new InMemoryUsuarioRepository();
        $this->repo->seed(['id' => 1, 'nombre' => 'Ana', 'apellido' => 'A', 'email' => 'admin@controlvet.com',
            'password_hash' => password_hash('Admin123', PASSWORD_BCRYPT, ['cost' => 4]), 'rol_id' => 1]);
        $this->repo->seed(['id' => 4, 'nombre' => 'Carlos', 'apellido' => 'R', 'email' => 'cliente@controlvet.com',
            'password_hash' => password_hash('Cliente123', PASSWORD_BCRYPT, ['cost' => 4]), 'rol_id' => 4, 'propietario_id' => 1]);
        $this->jwt = new Jwt(str_repeat('s', 40));
        $this->service = new AuthService($this->repo, $this->jwt);
    }

    public function testLoginCorrectoEmiteJwtConRolYPropietario(): void
    {
        $r = $this->service->login('cliente@controlvet.com', 'Cliente123');
        $claims = $this->jwt->decode($r['token']);

        self::assertSame(4, $claims['sub']);
        self::assertSame('cliente', $claims['rol']);
        self::assertSame(1, $claims['propietario_id']);
        self::assertArrayNotHasKey('password_hash', $r['usuario']);
    }

    public function testContrasenaIncorrectaRetorna401(): void
    {
        $this->expectExceptionObject(HttpException::unauthorized('Correo o contraseña incorrectos.'));
        $this->service->login('admin@controlvet.com', 'incorrecta');
    }

    public function testCorreoInexistenteRetornaMismoMensaje(): void
    {
        $this->expectExceptionMessage('Correo o contraseña incorrectos.');
        $this->service->login('nadie@controlvet.com', 'Admin123');
    }

    public function testUsuarioInactivoNoPuedeIniciarSesion(): void
    {
        $this->repo->setEstado(1, 'inactivo');
        $this->expectExceptionCode(401);
        $this->service->login('admin@controlvet.com', 'Admin123');
    }

    public function testCrearUsuarioHasheaContrasena(): void
    {
        $u = $this->service->crear(['nombre' => 'Vet', 'apellido' => 'Nuevo', 'email' => 'vet2@controlvet.com', 'password' => 'Secreta1', 'rol_id' => 3]);

        self::assertSame('veterinario', $u['rol_nombre']);
        self::assertTrue(password_verify('Secreta1', $this->repo->usuarios[$u['id']]['password_hash']));
    }

    public function testCorreoDuplicadoRetorna422(): void
    {
        try {
            $this->service->crear(['nombre' => 'X', 'apellido' => 'Y', 'email' => 'admin@controlvet.com', 'password' => '123456', 'rol_id' => 2]);
            self::fail('Se esperaba 422');
        } catch (HttpException $e) {
            self::assertSame(422, $e->status());
            self::assertSame('Ya existe un usuario registrado con este correo.', $e->errors()['email']);
        }
    }

    public function testRolInexistenteRetorna422(): void
    {
        $this->expectExceptionCode(422);
        $this->service->crear(['nombre' => 'X', 'apellido' => 'Y', 'email' => 'x@controlvet.com', 'password' => '123456', 'rol_id' => 99]);
    }

    public function testActualizarSinContrasenaConservaLaAnterior(): void
    {
        $hashAntes = $this->repo->usuarios[1]['password_hash'];
        $this->service->actualizar(1, ['nombre' => 'Ana María', 'apellido' => 'A', 'email' => 'admin@controlvet.com', 'rol_id' => 1, 'password' => '']);

        self::assertSame($hashAntes, $this->repo->usuarios[1]['password_hash']);
        self::assertSame('Ana María', $this->repo->usuarios[1]['nombre']);
    }

    public function testAdministradorNoPuedeDesactivarseASiMismo(): void
    {
        $this->expectExceptionCode(409);
        $this->service->cambiarEstado(1, 'inactivo', 1);
    }

    public function testDesactivarOtroUsuario(): void
    {
        self::assertSame('inactivo', $this->service->cambiarEstado(4, 'inactivo', 1)['estado']);
    }
}
