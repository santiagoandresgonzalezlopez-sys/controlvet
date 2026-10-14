<?php

declare(strict_types=1);

namespace ControlVet\Auth\Tests\Unit;

use ControlVet\Auth\Validador\UsuarioValidador;
use ControlVet\Kernel\Http\HttpException;
use PHPUnit\Framework\TestCase;

final class UsuarioValidadorTest extends TestCase
{
    private function errores(callable $fn): array
    {
        try {
            $fn();
        } catch (HttpException $e) {
            return $e->errors();
        }
        return [];
    }

    public function testLoginExigeCorreoValidoYContrasena(): void
    {
        $e = $this->errores(fn() => UsuarioValidador::login(['email' => 'malo']));
        self::assertArrayHasKey('email', $e);
        self::assertArrayHasKey('password', $e);
    }

    public function testNuevoUsuarioExigeContrasenaMinima(): void
    {
        $e = $this->errores(fn() => UsuarioValidador::guardar(
            ['nombre' => 'A', 'apellido' => 'B', 'email' => 'a@b.co', 'rol_id' => 2, 'password' => '123'], true
        ));
        self::assertSame(['password' => 'El campo contraseña debe tener al menos 6 caracteres.'], $e);
    }

    public function testEdicionNoExigeContrasena(): void
    {
        self::assertSame([], $this->errores(fn() => UsuarioValidador::guardar(
            ['nombre' => 'A', 'apellido' => 'B', 'email' => 'a@b.co', 'rol_id' => '2'], false
        )));
    }

    public function testEstadoSoloActivoOInactivo(): void
    {
        self::assertArrayHasKey('estado', $this->errores(fn() => UsuarioValidador::estado(['estado' => 'borrado'])));
    }
}
