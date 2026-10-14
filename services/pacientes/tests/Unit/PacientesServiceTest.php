<?php

declare(strict_types=1);

namespace ControlVet\Pacientes\Tests\Unit;

use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Security\AuthContext;
use ControlVet\Pacientes\Microservicio\PacientesService;
use ControlVet\Pacientes\Tests\InMemoryPacientesRepository;
use PHPUnit\Framework\TestCase;

final class PacientesServiceTest extends TestCase
{
    private InMemoryPacientesRepository $repo;
    private PacientesService $service;

    protected function setUp(): void
    {
        $this->repo = new InMemoryPacientesRepository();
        $this->service = new PacientesService($this->repo);
        $this->repo->crearPropietario(['nombre' => 'Carlos', 'apellido' => 'Ramírez', 'documento' => '1098765432', 'telefono' => '300']);
        $this->repo->crearPropietario(['nombre' => 'María', 'apellido' => 'Gómez', 'documento' => '1087654321', 'telefono' => '300']);
        $this->repo->crearMascota(['nombre' => 'Rocky', 'especie' => 'Perro', 'sexo' => 'macho', 'propietario_id' => 1]);
        $this->repo->crearMascota(['nombre' => 'Luna', 'especie' => 'Perro', 'sexo' => 'hembra', 'propietario_id' => 2]);
    }

    private static function cliente(?int $propietarioId): AuthContext
    {
        return AuthContext::fromVerifiedClaims(['sub' => 4, 'rol' => 'cliente', 'propietario_id' => $propietarioId]);
    }

    private static function staff(string $rol = 'recepcionista'): AuthContext
    {
        return AuthContext::fromVerifiedClaims(['sub' => 2, 'rol' => $rol]);
    }

    public function testDocumentoDuplicadoRetorna422(): void
    {
        try {
            $this->service->guardarPropietario(null, ['nombre' => 'X', 'apellido' => 'Y', 'documento' => '1098765432', 'telefono' => '1']);
            self::fail('Se esperaba 422');
        } catch (HttpException $e) {
            self::assertSame('Ya existe un propietario registrado con este documento.', $e->errors()['documento']);
        }
    }

    public function testEditarPropietarioConSuMismoDocumentoEsValido(): void
    {
        $p = $this->service->guardarPropietario(1, ['nombre' => 'Carlos A.', 'apellido' => 'Ramírez', 'documento' => '1098765432', 'telefono' => '301']);
        self::assertSame('Carlos A.', $p['nombre']);
    }

    public function testNoSeDesactivaPropietarioConMascotasActivas(): void
    {
        $this->expectExceptionObject(HttpException::conflict('No es posible desactivar: el propietario tiene mascotas activas asociadas.'));
        $this->service->desactivarPropietario(1);
    }

    public function testDesactivarPropietarioSinMascotasActivas(): void
    {
        $this->repo->mascotas[2]['estado'] = 'inactivo';
        self::assertSame('inactivo', $this->service->desactivarPropietario(2)['estado']);
    }

    public function testMascotaRequierePropietarioActivo(): void
    {
        $this->repo->propietarios[2]['estado'] = 'inactivo';
        $this->expectExceptionCode(422);
        $this->service->guardarMascota(null, ['nombre' => 'Toby', 'especie' => 'Perro', 'sexo' => 'macho', 'propietario_id' => 2]);
    }

    public function testMascotaConPropietarioInexistenteRetorna422(): void
    {
        $this->expectExceptionCode(422);
        $this->service->guardarMascota(null, ['nombre' => 'Toby', 'especie' => 'Perro', 'sexo' => 'macho', 'propietario_id' => 99]);
    }

    public function testClienteSoloVeSusMascotasAunqueFiltrePorOtroPropietario(): void
    {
        $mascotas = $this->service->mascotas(self::cliente(1), '', 2, []);
        self::assertSame(['Rocky'], array_column($mascotas, 'nombre'));
    }

    public function testClienteSinPropietarioNoVeMascotas(): void
    {
        self::assertSame([], $this->service->mascotas(self::cliente(null), '', null, []));
    }

    public function testClienteNoPuedeVerMascotaAjena(): void
    {
        $this->expectExceptionCode(403);
        $this->service->mascota(self::cliente(1), 2);
    }

    public function testPersonalVeTodasLasMascotas(): void
    {
        self::assertCount(2, $this->service->mascotas(self::staff('veterinario'), '', null, []));
    }

    public function testActualizarPesoDeMascota(): void
    {
        self::assertSame(29.5, $this->service->actualizarPeso(1, 29.5)['peso_actual']);
    }

    public function testResumenCuentaSoloActivos(): void
    {
        $this->repo->mascotas[2]['estado'] = 'inactivo';
        self::assertSame(['propietarios' => 2, 'mascotas' => 1], $this->service->resumen());
    }
}
