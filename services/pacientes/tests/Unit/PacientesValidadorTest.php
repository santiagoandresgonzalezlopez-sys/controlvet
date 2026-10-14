<?php

declare(strict_types=1);

namespace ControlVet\Pacientes\Tests\Unit;

use ControlVet\Kernel\Http\HttpException;
use ControlVet\Pacientes\Validador\PacientesValidador;
use PHPUnit\Framework\TestCase;

final class PacientesValidadorTest extends TestCase
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

    public function testMascotaSinPropietarioEsRechazada(): void
    {
        $e = $this->errores(fn() => PacientesValidador::mascota(['nombre' => 'Toby', 'especie' => 'Perro', 'sexo' => 'macho']));
        self::assertSame('El campo propietario es obligatorio.', $e['propietario_id']);
    }

    public function testSexoInvalidoYPesoNegativo(): void
    {
        $e = $this->errores(fn() => PacientesValidador::mascota(
            ['nombre' => 'Toby', 'especie' => 'Perro', 'sexo' => 'otro', 'propietario_id' => 1, 'peso_actual' => '-3']
        ));
        self::assertArrayHasKey('sexo', $e);
        self::assertSame('El peso no puede ser negativo.', $e['peso_actual']);
    }

    public function testFechaNacimientoFuturaEsRechazada(): void
    {
        $e = $this->errores(fn() => PacientesValidador::mascota(
            ['nombre' => 'Toby', 'especie' => 'Perro', 'sexo' => 'macho', 'propietario_id' => 1, 'fecha_nacimiento' => date('Y-m-d', strtotime('+1 day'))]
        ));
        self::assertArrayHasKey('fecha_nacimiento', $e);
    }

    public function testPropietarioExigeCamposYCorreoValido(): void
    {
        $e = $this->errores(fn() => PacientesValidador::propietario(['nombre' => 'A', 'email' => 'x']));
        self::assertSame(['apellido', 'documento', 'telefono', 'email'], array_keys($e));
    }

    public function testPesoFueraDeRango(): void
    {
        self::assertArrayHasKey('peso', $this->errores(fn() => PacientesValidador::peso(['peso' => 0])));
        self::assertSame([], $this->errores(fn() => PacientesValidador::peso(['peso' => 12.4])));
    }
}
