<?php

declare(strict_types=1);

namespace ControlVet\Inventario\Tests\Unit;

use ControlVet\Inventario\Microservicio\InventarioService;
use ControlVet\Inventario\Microservicio\ProductoRepository;
use ControlVet\Inventario\Validador\ProductoValidador;
use ControlVet\Kernel\Http\HttpException;
use PHPUnit\Framework\TestCase;

final class InventarioServiceTest extends TestCase
{
    private ProductoRepository $repo;
    private InventarioService $service;

    protected function setUp(): void
    {
        $this->repo = new class implements ProductoRepository {
            /** @var array<int,array<string,mixed>> */
            public array $p = [];
            public function activos(string $buscar): array { return array_values(array_filter($this->p, fn($x) => $x['estado'] === 'activo')); }
            public function stockBajo(): array { return array_values(array_filter($this->activos(''), [InventarioService::class, 'esStockBajo'])); }
            public function find(int $id): ?array { return $this->p[$id] ?? null; }
            public function create(array $d): int { $id = count($this->p) + 1; $this->p[$id] = ['id' => $id, 'estado' => 'activo'] + $d; return $id; }
            public function update(int $id, array $d): void { $this->p[$id] = array_merge($this->p[$id], $d); }
            public function actualizarCantidad(int $id, int $c): void { $this->p[$id]['cantidad_disponible'] = $c; }
            public function desactivar(int $id): void { $this->p[$id]['estado'] = 'inactivo'; }
            public function contarActivos(): int { return count($this->activos('')); }
        };
        $this->service = new InventarioService($this->repo);
        $this->service->guardar(null, ['nombre' => 'Vacuna', 'categoria' => 'Biológico', 'cantidad_disponible' => 40, 'stock_minimo' => 10]);
        $this->service->guardar(null, ['nombre' => 'Jeringas', 'categoria' => 'Insumo', 'cantidad_disponible' => 3, 'stock_minimo' => 5]);
    }

    public function testCrearMarcaIndicadorDeStockBajo(): void
    {
        $p = $this->service->guardar(null, ['nombre' => 'Suero', 'categoria' => 'Insumo', 'cantidad_disponible' => 2, 'stock_minimo' => 8]);
        self::assertTrue($p['stock_bajo']);
        self::assertFalse($this->service->actualizarStock(1, 11)['stock_bajo']);
    }

    public function testStockIgualAlMinimoEsStockBajo(): void
    {
        self::assertTrue($this->service->actualizarStock(1, 10)['stock_bajo']);
    }

    public function testEditarNoModificaLaCantidadDisponible(): void
    {
        $p = $this->service->guardar(1, ['nombre' => 'Vacuna 5en1', 'categoria' => 'Biológico', 'stock_minimo' => 12, 'cantidad_disponible' => 999]);
        self::assertSame(40, $p['cantidad_disponible']);
        self::assertSame('Vacuna 5en1', $p['nombre']);
    }

    public function testNoSeActualizaStockDeProductoInactivo(): void
    {
        $this->service->desactivar(2);
        $this->expectExceptionCode(409);
        $this->service->actualizarStock(2, 50);
    }

    public function testProductoInexistenteRetorna404(): void
    {
        $this->expectExceptionCode(404);
        $this->service->actualizarStock(99, 1);
    }

    public function testResumen(): void
    {
        self::assertSame(['total' => 2, 'stock_bajo' => 1], $this->service->resumen());
    }

    public function testValidadorRechazaNegativos(): void
    {
        try {
            ProductoValidador::guardar(['nombre' => 'X', 'categoria' => 'Y', 'stock_minimo' => '-1', 'cantidad_disponible' => '-5', 'precio_referencia' => '-3'], true);
            self::fail('Se esperaba 422');
        } catch (HttpException $e) {
            self::assertSame(['stock_minimo', 'precio_referencia', 'cantidad_disponible'], array_keys($e->errors()));
        }
    }

    public function testValidadorStockExigeEnteroNoNegativo(): void
    {
        $this->expectExceptionCode(422);
        ProductoValidador::stock(['cantidad_disponible' => 'diez']);
    }
}
