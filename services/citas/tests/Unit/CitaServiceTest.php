<?php

declare(strict_types=1);

namespace ControlVet\Citas\Tests\Unit;

use ControlVet\Citas\Microservicio\CitaService;
use ControlVet\Citas\Tests\FakeDirectorioClinico;
use ControlVet\Citas\Tests\InMemoryCitaRepository;
use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Security\AuthContext;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Fakes.php';

final class CitaServiceTest extends TestCase
{
    private InMemoryCitaRepository $repo;
    private CitaService $service;

    protected function setUp(): void
    {
        $this->repo = new InMemoryCitaRepository();
        $this->service = new CitaService($this->repo, new FakeDirectorioClinico());
        $hoy = date('Y-m-d');
        $this->repo->create(['mascota_id' => 1, 'propietario_id' => 1, 'veterinario_id' => 3, 'fecha' => $hoy, 'hora' => '09:00:00', 'motivo' => 'Control']);
        $this->repo->create(['mascota_id' => 3, 'propietario_id' => 2, 'veterinario_id' => 3, 'fecha' => $hoy, 'hora' => '11:30:00', 'motivo' => 'Cojera']);
        $this->repo->create(['mascota_id' => 3, 'propietario_id' => 2, 'veterinario_id' => 7, 'fecha' => $hoy, 'hora' => '12:00:00', 'motivo' => 'Otro vet']);
    }

    private static function ctx(string $rol, int $sub = 2, ?int $propietarioId = null): AuthContext
    {
        return AuthContext::fromVerifiedClaims(['sub' => $sub, 'rol' => $rol, 'propietario_id' => $propietarioId]);
    }

    /** @return array<string,mixed> */
    private static function cita(array $extra = []): array
    {
        return $extra + ['mascota_id' => 1, 'veterinario_id' => 3, 'fecha' => date('Y-m-d', strtotime('+1 day')), 'hora' => '10:00', 'motivo' => 'Vacuna'];
    }

    public function testRecepcionAgendaCitaYCopiaPropietarioDeLaMascota(): void
    {
        $c = $this->service->guardar(self::ctx('recepcionista'), null, self::cita(['mascota_id' => 3]));

        self::assertSame(2, $c['propietario_id']);
        self::assertSame('10:00:00', $c['hora']);
        self::assertSame(2, $c['creado_por']);
    }

    public function testNoSeAgendaConMascotaInexistenteOInactiva(): void
    {
        foreach ([99, 9] as $mascota) {
            try {
                $this->service->guardar(self::ctx('recepcionista'), null, self::cita(['mascota_id' => $mascota]));
                self::fail('Se esperaba 422');
            } catch (HttpException $e) {
                self::assertSame('No es posible registrar una cita sin paciente.', $e->errors()['mascota_id']);
            }
        }
    }

    public function testNoSeAgendaConVeterinarioInactivo(): void
    {
        $this->expectExceptionCode(422);
        $this->service->guardar(self::ctx('administrador', 1), null, self::cita(['veterinario_id' => 5]));
    }

    public function testNoSePermiteDobleReservaDelVeterinario(): void
    {
        try {
            $this->service->guardar(self::ctx('recepcionista'), null, self::cita(['fecha' => date('Y-m-d'), 'hora' => '09:00']));
            self::fail('Se esperaba 422');
        } catch (HttpException $e) {
            self::assertSame('El veterinario ya tiene una cita asignada en esa fecha y hora.', $e->errors()['hora']);
        }
    }

    public function testReprogramarLaMismaCitaEnSuHorarioNoEsConflicto(): void
    {
        $c = $this->service->guardar(self::ctx('recepcionista'), 1, self::cita(['fecha' => date('Y-m-d'), 'hora' => '09:00', 'motivo' => 'Control editado']));
        self::assertSame('Control editado', $c['motivo']);
    }

    public function testHorarioDeCitaCanceladaQuedaLibre(): void
    {
        $this->repo->cambiarEstado(1, 'cancelada');
        $c = $this->service->guardar(self::ctx('recepcionista'), null, self::cita(['fecha' => date('Y-m-d'), 'hora' => '09:00']));
        self::assertSame('programada', $c['estado']);
    }

    public function testVeterinarioSoloVeSuAgendaAunqueFiltrePorOtro(): void
    {
        $citas = $this->service->listar(self::ctx('veterinario', 3), ['veterinario_id' => 7]);
        self::assertSame([1, 2], array_column($citas, 'id'));
    }

    public function testClienteSoloVeCitasDeSusMascotas(): void
    {
        $citas = $this->service->listar(self::ctx('cliente', 4, 1), ['propietario_id' => 2]);
        self::assertSame([1], array_column($citas, 'id'));
    }

    public function testClienteNoPuedeVerCitaAjena(): void
    {
        $this->expectExceptionCode(403);
        $this->service->ver(self::ctx('cliente', 4, 1), 2);
    }

    public function testVeterinarioMarcaAtendidaSuPropiaCita(): void
    {
        self::assertSame('atendida', $this->service->cambiarEstado(self::ctx('veterinario', 3), 1, 'atendida')['estado']);
    }

    public function testVeterinarioNoPuedeCambiarCitaDeOtroVeterinario(): void
    {
        $this->expectExceptionCode(403);
        $this->service->cambiarEstado(self::ctx('veterinario', 3), 3, 'atendida');
    }

    public function testVeterinarioNoPuedeCancelarCitas(): void
    {
        $this->expectExceptionCode(403);
        $this->service->cambiarEstado(self::ctx('veterinario', 3), 1, 'cancelada');
    }

    public function testRecepcionCancelaCita(): void
    {
        self::assertSame('cancelada', $this->service->cambiarEstado(self::ctx('recepcionista'), 3, 'cancelada')['estado']);
    }

    public function testProximasExcluyeCanceladasYRespetaAlcanceDelCliente(): void
    {
        $this->repo->cambiarEstado(2, 'cancelada');
        self::assertSame([3], array_column($this->service->proximas(self::ctx('cliente', 4, 2), 5, null, null), 'id'));
    }
}
