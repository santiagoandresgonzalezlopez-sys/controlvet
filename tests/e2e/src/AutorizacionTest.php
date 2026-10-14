<?php

declare(strict_types=1);

namespace ControlVet\E2E;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Matriz de permisos por rol aplicada por CADA servicio (no solo por el frontend).
 */
final class AutorizacionTest extends TestCase
{
    public static function matriz(): array
    {
        return [
            // rol, método, servicio, ruta, estado esperado
            'cliente no lista usuarios'          => ['cliente', 'GET', 'auth', '/usuarios', 403],
            'recepción no lista usuarios'        => ['recepcionista', 'GET', 'auth', '/usuarios', 403],
            'admin lista usuarios'               => ['administrador', 'GET', 'auth', '/usuarios', 200],
            'recepción no ve inventario'         => ['recepcionista', 'GET', 'inventario', '/productos', 403],
            'veterinario no ve inventario'       => ['veterinario', 'GET', 'inventario', '/productos', 403],
            'admin ve inventario'                => ['administrador', 'GET', 'inventario', '/productos', 200],
            'cliente no lista propietarios'      => ['cliente', 'GET', 'pacientes', '/propietarios', 403],
            'veterinario no lista propietarios'  => ['veterinario', 'GET', 'pacientes', '/propietarios', 403],
            'veterinario ve pacientes'           => ['veterinario', 'GET', 'pacientes', '/mascotas', 200],
            'veterinario no agenda citas'        => ['veterinario', 'POST', 'citas', '/citas', 403],
            'cliente no agenda citas'            => ['cliente', 'POST', 'citas', '/citas', 403],
            'recepción no registra consultas'    => ['recepcionista', 'POST', 'clinica', '/historias', 403],
            'admin no registra consultas'        => ['administrador', 'POST', 'clinica', '/historias', 403],
            'cliente no crea mascotas'           => ['cliente', 'POST', 'pacientes', '/mascotas', 403],
            'veterinario no edita mascotas'      => ['veterinario', 'PUT', 'pacientes', '/mascotas/1', 403],
            'recepción no cambia peso'           => ['recepcionista', 'PATCH', 'pacientes', '/mascotas/1/peso', 403],
        ];
    }

    #[DataProvider('matriz')]
    public function testPermiso(string $rol, string $metodo, string $servicio, string $ruta, int $esperado): void
    {
        $body = in_array($metodo, ['POST', 'PUT', 'PATCH'], true) ? [] : null;
        $r = Http::api($metodo, $servicio, $ruta, Http::token($rol), $body);

        self::assertSame($esperado, $r['status'], "$rol $metodo $servicio$ruta");
    }
}
