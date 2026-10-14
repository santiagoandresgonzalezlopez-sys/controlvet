<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Tests;

use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testCamposValidosPasan(): void
    {
        $v = (new Validator(['email' => 'a@b.co', 'fecha' => '2026-02-28', 'hora' => '09:30', 'n' => '3']))
            ->required('email', 'correo')->email('email')->date('fecha', 'cita')->time('hora', 'cita')->integer('n', 'n');

        self::assertTrue($v->passes());
    }

    public function testMensajesDeErrorPorCampo(): void
    {
        $v = (new Validator(['email' => 'no-es-correo', 'fecha' => '2026-02-30', 'peso' => 'abc']))
            ->required('nombre', 'nombre')->email('email')->date('fecha', 'la cita')->numeric('peso', 'peso');

        self::assertSame([
            'nombre' => 'El campo nombre es obligatorio.',
            'email' => 'El correo electrónico no tiene un formato válido.',
            'fecha' => 'La fecha de la cita no es válida.',
            'peso' => 'El campo peso debe ser numérico.',
        ], $v->errors());
    }

    public function testValidateLanza422ConErrores(): void
    {
        try {
            (new Validator([]))->required('motivo', 'motivo')->validate();
            self::fail('Se esperaba HttpException');
        } catch (HttpException $e) {
            self::assertSame(422, $e->status());
            self::assertArrayHasKey('motivo', $e->errors());
        }
    }

    public function testEnteroNegativoEsRechazado(): void
    {
        self::assertFalse((new Validator(['c' => '-1']))->integer('c', 'cantidad')->passes());
    }
}
