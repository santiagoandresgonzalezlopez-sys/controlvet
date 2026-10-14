<?php

declare(strict_types=1);

namespace ControlVet\Citas\Tests\Unit;

use ControlVet\Citas\Validador\CitaValidador;
use ControlVet\Kernel\Http\HttpException;
use PHPUnit\Framework\TestCase;

final class CitaValidadorTest extends TestCase
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

    public function testCitaSinPacienteNiVeterinario(): void
    {
        $e = $this->errores(fn() => CitaValidador::guardar(['fecha' => '2026-10-20', 'hora' => '10:00', 'motivo' => 'x']));
        self::assertSame('El campo paciente es obligatorio.', $e['mascota_id']);
        self::assertSame('El campo veterinario es obligatorio.', $e['veterinario_id']);
    }

    public function testFechaYHoraInvalidas(): void
    {
        $e = $this->errores(fn() => CitaValidador::guardar(['mascota_id' => 1, 'veterinario_id' => 3, 'fecha' => '2026-13-01', 'hora' => '25:00', 'motivo' => 'x']));
        self::assertSame(['fecha', 'hora'], array_keys($e));
    }

    public function testEstadoDesconocidoEsRechazado(): void
    {
        self::assertArrayHasKey('estado', $this->errores(fn() => CitaValidador::estado(['estado' => 'borrada'])));
    }

    public function testFiltrosInvalidosSonRechazados(): void
    {
        self::assertSame(['fecha', 'veterinario_id'], array_keys($this->errores(fn() => CitaValidador::filtros(['fecha' => 'hoy', 'veterinario_id' => 'abc']))));
    }
}
