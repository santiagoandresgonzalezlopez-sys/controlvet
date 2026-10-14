<?php

declare(strict_types=1);

namespace ControlVet\Documentos\Tests\Unit;

use ControlVet\Documentos\Microservicio\DocumentoRepository;
use ControlVet\Documentos\Microservicio\DocumentoService;
use ControlVet\Documentos\Microservicio\FuentesDocumentales;
use ControlVet\Documentos\Validador\DocumentoValidador;
use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Security\AuthContext;
use PHPUnit\Framework\TestCase;

final class DocumentoServiceTest extends TestCase
{
    private DocumentoRepository $repo;
    private DocumentoService $service;

    protected function setUp(): void
    {
        $this->repo = new class implements DocumentoRepository {
            /** @var array<int,array<string,mixed>> */
            public array $docs = [];
            public function registrar(string $tipo, int $ref, string $nombre, int $por, int $prop): int
            {
                $this->docs[] = ['tipo' => $tipo, 'referencia_id' => $ref, 'nombre_archivo' => $nombre, 'generado_por' => $por, 'propietario_id' => $prop];
                return count($this->docs);
            }
            public function todos(): array { return $this->docs; }
            public function porPropietario(int $p): array { return array_values(array_filter($this->docs, fn($d) => $d['propietario_id'] === $p)); }
        };

        $fuentes = new class implements FuentesDocumentales {
            public function cita(int $id): ?array
            {
                return $id === 1 ? ['id' => 1, 'mascota_id' => 1, 'propietario_id' => 1, 'veterinario_id' => 3, 'fecha' => '2026-10-14',
                    'hora' => '09:00:00', 'motivo' => 'Control anual y vacunación', 'estado' => 'confirmada', 'observaciones' => null] : null;
            }
            public function historia(int $id): ?array
            {
                return $id === 1 ? ['id' => 1, 'mascota_id' => 4, 'propietario_id' => 3, 'veterinario_id' => 3, 'fecha' => '2026-10-09 10:00:00',
                    'motivo_consulta' => 'Chequeo displasia', 'peso' => '25.00', 'sintomas' => 'Cojera', 'diagnostico' => 'Displasia grado I',
                    'tratamiento' => 'Condroprotector', 'observaciones' => null] : null;
            }
            public function formula(int $id): ?array
            {
                return $id === 1 ? ['id' => 1, 'historia_id' => 1, 'medicamento' => 'Condroitina', 'dosis' => '1 tableta',
                    'frecuencia' => 'Cada 24 horas', 'duracion' => '30 días', 'indicaciones' => null, 'fecha_creacion' => '2026-10-09 10:10:00'] : null;
            }
            public function mascota(int $id): ?array
            {
                return ['nombre' => $id === 1 ? 'Rocky' : 'Max', 'especie' => 'Perro', 'propietario_nombre' => $id === 1 ? 'Carlos' : 'Luis', 'propietario_apellido' => $id === 1 ? 'Ramírez' : 'Torres'];
            }
            public function veterinario(int $id): ?array
            {
                return ['nombre' => 'Dr. Jorge', 'apellido' => 'Martínez'];
            }
        };

        $this->service = new DocumentoService($this->repo, $fuentes);
    }

    private static function ctx(string $rol, ?int $propietarioId = null): AuthContext
    {
        return AuthContext::fromVerifiedClaims(['sub' => 9, 'rol' => $rol, 'propietario_id' => $propietarioId]);
    }

    public function testComprobanteEsPdfValidoYQuedaRegistrado(): void
    {
        [$nombre, $pdf] = $this->service->comprobanteCita(self::ctx('cliente', 1), 1);

        self::assertSame('comprobante_cita_1.pdf', $nombre);
        self::assertStringStartsWith('%PDF-1.4', $pdf);
        self::assertStringEndsWith('%%EOF', $pdf);
        self::assertSame(['tipo' => 'comprobante_cita', 'referencia_id' => 1, 'nombre_archivo' => $nombre, 'generado_por' => 9, 'propietario_id' => 1], $this->repo->docs[0]);
    }

    public function testTildesSeCodificanEnWinAnsi(): void
    {
        [, $pdf] = $this->service->comprobanteCita(self::ctx('recepcionista'), 1);

        self::assertStringContainsString('/Encoding /WinAnsiEncoding', $pdf);
        self::assertStringContainsString(mb_convert_encoding('Ramírez', 'Windows-1252', 'UTF-8'), $pdf);
        self::assertStringNotContainsString('Ramírez', $pdf); // sin bytes UTF-8 crudos
    }

    public function testClienteNoGeneraDocumentoDeOtroPropietario(): void
    {
        try {
            $this->service->resumenConsulta(self::ctx('cliente', 1), 1);
            self::fail('Se esperaba 403');
        } catch (HttpException $e) {
            self::assertSame(403, $e->status());
            self::assertSame([], $this->repo->docs, 'No debe registrarse un documento denegado');
        }
    }

    public function testFormulaMedicaHeredaPropietarioDeLaHistoria(): void
    {
        $this->service->formulaMedica(self::ctx('veterinario'), 1);
        self::assertSame(3, $this->repo->docs[0]['propietario_id']);
    }

    public function testReferenciaInexistenteRetorna404(): void
    {
        $this->expectExceptionCode(404);
        $this->service->comprobanteCita(self::ctx('administrador'), 99);
    }

    public function testListadoDelClienteSoloIncluyeSusDocumentos(): void
    {
        $this->service->comprobanteCita(self::ctx('recepcionista'), 1);
        $this->service->resumenConsulta(self::ctx('veterinario'), 1);

        self::assertCount(1, $this->service->listar(self::ctx('cliente', 1)));
        self::assertCount(2, $this->service->listar(self::ctx('administrador')));
        self::assertSame([], $this->service->listar(self::ctx('cliente', null)));
    }

    public function testReferenciaFueraDeRango(): void
    {
        $this->expectExceptionCode(422);
        DocumentoValidador::referencia(0);
    }
}
