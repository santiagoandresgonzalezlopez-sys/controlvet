<?php

declare(strict_types=1);

namespace ControlVet\E2E;

use PHPUnit\Framework\TestCase;

final class PacientesApiTest extends TestCase
{
    public function testFlujoPropietarioMascotaYDesactivacion(): void
    {
        $recep = Http::token('recepcionista');
        $doc = 'E2E' . random_int(1000000, 9999999);

        $p = Http::api('POST', 'pacientes', '/propietarios', $recep, [
            'nombre' => 'Prueba', 'apellido' => 'Integración', 'documento' => $doc, 'telefono' => '3000000000', 'email' => 'e2e@controlvet.test',
        ]);
        self::assertSame(201, $p['status']);
        $propietarioId = (int)$p['json']['id'];

        $duplicado = Http::api('POST', 'pacientes', '/propietarios', $recep, [
            'nombre' => 'Otro', 'apellido' => 'X', 'documento' => $doc, 'telefono' => '1',
        ]);
        self::assertSame(422, $duplicado['status']);
        self::assertSame('Ya existe un propietario registrado con este documento.', $duplicado['json']['errors']['documento']);

        $m = Http::api('POST', 'pacientes', '/mascotas', $recep, [
            'nombre' => 'Firulais', 'especie' => 'Perro', 'sexo' => 'macho', 'propietario_id' => $propietarioId, 'peso_actual' => '12.5',
        ]);
        self::assertSame(201, $m['status']);
        self::assertSame('Prueba', $m['json']['propietario_nombre']);

        $bloqueo = Http::api('PATCH', 'pacientes', "/propietarios/$propietarioId/desactivar", $recep, []);
        self::assertSame(409, $bloqueo['status']);
        self::assertSame('No es posible desactivar: el propietario tiene mascotas activas asociadas.', $bloqueo['json']['error']);

        $mascotas = Http::api('GET', 'pacientes', "/propietarios/$propietarioId/mascotas", $recep);
        self::assertSame(['Firulais'], array_column($mascotas['json'], 'nombre'));
    }

    public function testMascotaSinPropietarioEsRechazada(): void
    {
        $r = Http::api('POST', 'pacientes', '/mascotas', Http::token('recepcionista'), ['nombre' => 'Sin dueño', 'especie' => 'Gato', 'sexo' => 'hembra']);

        self::assertSame(422, $r['status']);
        self::assertArrayHasKey('propietario_id', $r['json']['errors']);
    }

    public function testMascotaConPropietarioInexistenteEsRechazada(): void
    {
        $r = Http::api('POST', 'pacientes', '/mascotas', Http::token('administrador'), [
            'nombre' => 'Huérfano', 'especie' => 'Gato', 'sexo' => 'hembra', 'propietario_id' => 999999,
        ]);
        self::assertSame(422, $r['status']);
    }

    public function testBusquedaDeMascotasPorNombreDePropietario(): void
    {
        $r = Http::api('GET', 'pacientes', '/mascotas?buscar=Ram%C3%ADrez', Http::token('veterinario'));
        self::assertContains('Rocky', array_column($r['json'], 'nombre'));
    }
}
