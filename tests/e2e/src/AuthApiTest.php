<?php

declare(strict_types=1);

namespace ControlVet\E2E;

use PHPUnit\Framework\TestCase;

final class AuthApiTest extends TestCase
{
    public function testLoginDevuelveTokenYUsuarioSinHash(): void
    {
        $r = Http::api('POST', 'auth', '/auth/login', null, ['email' => 'veterinario@controlvet.com', 'password' => 'Vet123']);

        self::assertSame(200, $r['status']);
        self::assertSame(2, substr_count($r['json']['token'], '.'));
        self::assertSame('veterinario', $r['json']['usuario']['rol_nombre']);
        self::assertArrayNotHasKey('password_hash', $r['json']['usuario']);
    }

    public function testCredencialesIncorrectasRetornan401(): void
    {
        $r = Http::api('POST', 'auth', '/auth/login', null, ['email' => 'admin@controlvet.com', 'password' => 'mala']);
        self::assertSame(401, $r['status']);
        self::assertSame('Correo o contraseña incorrectos.', $r['json']['error']);
    }

    public function testLoginSinDatosRetorna422ConErroresPorCampo(): void
    {
        $r = Http::api('POST', 'auth', '/auth/login', null, ['email' => 'no-es-correo']);
        self::assertSame(422, $r['status']);
        self::assertArrayHasKey('email', $r['json']['errors']);
        self::assertArrayHasKey('password', $r['json']['errors']);
    }

    public function testMeIdentificaAlUsuarioDelToken(): void
    {
        $r = Http::api('GET', 'auth', '/auth/me', Http::token('cliente'));
        self::assertSame(200, $r['status']);
        self::assertSame('cliente@controlvet.com', $r['json']['email']);
    }

    public function testCadaServicioRechazaPeticionesSinToken(): void
    {
        $rutas = ['auth' => '/usuarios', 'pacientes' => '/mascotas', 'citas' => '/citas', 'clinica' => '/historias',
            'inventario' => '/productos', 'documentos' => '/documentos'];
        foreach ($rutas as $servicio => $ruta) {
            self::assertSame(401, Http::api('GET', $servicio, $ruta)['status'], "$servicio$ruta sin token");
        }
    }

    public function testTokenManipuladoEsRechazado(): void
    {
        [$h, $p, $s] = explode('.', Http::token('cliente'));
        $claims = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
        $claims['rol'] = 'administrador';
        $falso = rtrim(strtr(base64_encode(json_encode($claims)), '+/', '-_'), '=');

        self::assertSame(401, Http::api('GET', 'auth', '/usuarios', "$h.$falso.$s")['status']);
    }
}
