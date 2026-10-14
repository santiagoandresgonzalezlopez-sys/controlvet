<?php

declare(strict_types=1);

namespace ControlVet\Clinica\Tests\Unit;

use ControlVet\Clinica\Microservicio\ClinicaService;
use ControlVet\Clinica\Tests\FakeDirectorioPacientes;
use ControlVet\Clinica\Tests\InMemoryHistoriaRepository;
use ControlVet\Clinica\Validador\ClinicaValidador;
use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Security\AuthContext;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Fakes.php';

final class ClinicaServiceTest extends TestCase
{
    private InMemoryHistoriaRepository $repo;
    private FakeDirectorioPacientes $pacientes;
    private ClinicaService $service;
    private AuthContext $vet;

    protected function setUp(): void
    {
        $this->repo = new InMemoryHistoriaRepository();
        $this->pacientes = new FakeDirectorioPacientes();
        $this->service = new ClinicaService($this->repo, $this->pacientes);
        $this->vet = AuthContext::fromVerifiedClaims(['sub' => 3, 'rol' => 'veterinario']);
        $this->service->registrar($this->vet, ['mascota_id' => 4, 'motivo_consulta' => 'Chequeo displasia', 'diagnostico' => 'Displasia grado I', 'peso' => '25']);
        $this->service->registrar($this->vet, ['mascota_id' => 1, 'motivo_consulta' => 'Vacunación', 'diagnostico' => 'Sano']);
    }

    private static function cliente(int $propietarioId): AuthContext
    {
        return AuthContext::fromVerifiedClaims(['sub' => 4, 'rol' => 'cliente', 'propietario_id' => $propietarioId]);
    }

    public function testRegistroCopiaPropietarioYUsaVeterinarioAutenticado(): void
    {
        $h = $this->service->registrar($this->vet, ['mascota_id' => 1, 'veterinario_id' => 99, 'motivo_consulta' => 'Otitis', 'diagnostico' => 'Otitis externa', 'peso' => '28.1']);

        self::assertSame(1, $h['propietario_id']);
        self::assertSame(3, $h['veterinario_id']);
        self::assertTrue($h['peso_sincronizado']);
        self::assertSame(28.1, $this->pacientes->pesos[1]);
    }

    public function testSinPesoNoSeSincronizaNada(): void
    {
        self::assertArrayNotHasKey(1, $this->pacientes->pesos);
    }

    public function testFalloDeSincronizacionNoPierdeLaHistoria(): void
    {
        $this->pacientes->disponible = false;
        $h = $this->service->registrar($this->vet, ['mascota_id' => 4, 'motivo_consulta' => 'Control', 'diagnostico' => 'Estable', 'peso' => '24.5']);

        self::assertFalse($h['peso_sincronizado']);
        self::assertNotNull($this->repo->find($h['id']));
    }

    public function testNoSeRegistraConsultaDePacienteInactivoOInexistente(): void
    {
        foreach ([8, 77] as $mascota) {
            try {
                $this->service->registrar($this->vet, ['mascota_id' => $mascota, 'motivo_consulta' => 'x', 'diagnostico' => 'y']);
                self::fail('Se esperaba 422');
            } catch (HttpException $e) {
                self::assertSame(422, $e->status());
            }
        }
    }

    public function testClienteSoloVeHistorialDeSusMascotas(): void
    {
        $historias = $this->service->listar(self::cliente(1), ['propietario_id' => 3], 50);
        self::assertSame([1], array_column($historias, 'mascota_id'));
    }

    public function testClienteNoPuedeVerHistoriaNiFormulaAjena(): void
    {
        $this->service->crearFormula($this->vet, 1, ['medicamento' => 'Condroitina', 'dosis' => '1', 'frecuencia' => '24h', 'duracion' => '30 días']);

        foreach ([fn() => $this->service->ver(self::cliente(1), 1), fn() => $this->service->formula(self::cliente(1), 1)] as $accion) {
            try {
                $accion();
                self::fail('Se esperaba 403');
            } catch (HttpException $e) {
                self::assertSame(403, $e->status());
            }
        }
    }

    public function testFormulaQuedaAsociadaALaHistoria(): void
    {
        $f = $this->service->crearFormula($this->vet, 2, ['medicamento' => 'Amoxicilina', 'dosis' => '250mg', 'frecuencia' => '12h', 'duracion' => '7 días', 'indicaciones' => '']);

        self::assertSame(2, $f['historia_id']);
        self::assertNull($f['indicaciones']);
        self::assertCount(1, $this->service->formulas(self::cliente(1), 2));
    }

    public function testFormulaSobreHistoriaInexistenteRetorna404(): void
    {
        $this->expectExceptionCode(404);
        $this->service->crearFormula($this->vet, 999, ['medicamento' => 'X', 'dosis' => '1', 'frecuencia' => '1', 'duracion' => '1']);
    }

    public function testValidadorExigeDiagnosticoYPesoValido(): void
    {
        try {
            ClinicaValidador::historia(['mascota_id' => 1, 'motivo_consulta' => 'x', 'peso' => '-2']);
            self::fail('Se esperaba 422');
        } catch (HttpException $e) {
            self::assertSame(['diagnostico', 'peso'], array_keys($e->errors()));
        }
    }

    public function testValidadorFormulaExigeCamposObligatorios(): void
    {
        try {
            ClinicaValidador::formula(['medicamento' => 'X']);
            self::fail('Se esperaba 422');
        } catch (HttpException $e) {
            self::assertSame(['dosis', 'frecuencia', 'duracion'], array_keys($e->errors()));
        }
    }
}
