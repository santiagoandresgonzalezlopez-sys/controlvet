<?php

declare(strict_types=1);

namespace ControlVet\E2E;

use PHPUnit\Framework\TestCase;

final class CitasApiTest extends TestCase
{
    /** Horario único por ejecución para no chocar con ejecuciones previas. */
    private static function horario(): array
    {
        $dias = random_int(30, 3000);
        return [date('Y-m-d', strtotime("+$dias days")), sprintf('%02d:%02d', random_int(7, 18), random_int(0, 59))];
    }

    public function testRecepcionAgendaCitaConValidacionEntreServicios(): void
    {
        [$fecha, $hora] = self::horario();
        $r = Http::api('POST', 'citas', '/citas', Http::token('recepcionista'), [
            'mascota_id' => 3, 'veterinario_id' => 3, 'fecha' => $fecha, 'hora' => $hora, 'motivo' => 'Prueba E2E',
        ]);

        self::assertSame(201, $r['status']);
        self::assertSame(2, (int)$r['json']['propietario_id'], 'Copia del propietario obtenida del servicio pacientes');
        self::assertSame('programada', $r['json']['estado']);

        $doble = Http::api('POST', 'citas', '/citas', Http::token('administrador'), [
            'mascota_id' => 1, 'veterinario_id' => 3, 'fecha' => $fecha, 'hora' => $hora, 'motivo' => 'Doble reserva',
        ]);
        self::assertSame(422, $doble['status']);
        self::assertSame('El veterinario ya tiene una cita asignada en esa fecha y hora.', $doble['json']['errors']['hora']);
    }

    public function testNoSeAgendaConMascotaOVeterinarioInexistentes(): void
    {
        [$fecha, $hora] = self::horario();
        $r = Http::api('POST', 'citas', '/citas', Http::token('recepcionista'), [
            'mascota_id' => 999999, 'veterinario_id' => 1, 'fecha' => $fecha, 'hora' => $hora, 'motivo' => 'x',
        ]);

        self::assertSame(422, $r['status']);
        self::assertSame('No es posible registrar una cita sin paciente.', $r['json']['errors']['mascota_id']);
        // El usuario 1 existe pero es administrador, no veterinario.
        self::assertSame('No es posible registrar una cita sin veterinario.', $r['json']['errors']['veterinario_id']);
    }

    public function testVeterinarioSoloMarcaAtendidasSusCitas(): void
    {
        [$fecha, $hora] = self::horario();
        $cita = Http::api('POST', 'citas', '/citas', Http::token('recepcionista'), [
            'mascota_id' => 1, 'veterinario_id' => 3, 'fecha' => $fecha, 'hora' => $hora, 'motivo' => 'Atención E2E',
        ])['json'];
        $vet = Http::token('veterinario');

        self::assertSame(403, Http::api('PATCH', 'citas', "/citas/{$cita['id']}/estado", $vet, ['estado' => 'cancelada'])['status']);
        $ok = Http::api('PATCH', 'citas', "/citas/{$cita['id']}/estado", $vet, ['estado' => 'atendida']);
        self::assertSame(200, $ok['status']);
        self::assertSame('atendida', $ok['json']['estado']);
    }

    public function testVeterinarioVeSoloSuAgenda(): void
    {
        $r = Http::api('GET', 'citas', '/citas?veterinario_id=999', Http::token('veterinario'));
        foreach ($r['json'] as $c) {
            self::assertSame(3, (int)$c['veterinario_id']);
        }
    }

    public function testEstadoInvalidoEsRechazado(): void
    {
        $r = Http::api('PATCH', 'citas', '/citas/1/estado', Http::token('recepcionista'), ['estado' => 'borrada']);
        self::assertSame(422, $r['status']);
    }
}
