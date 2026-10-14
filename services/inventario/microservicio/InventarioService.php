<?php

declare(strict_types=1);

namespace ControlVet\Inventario\Microservicio;

use ControlVet\Kernel\Http\HttpException;

/**
 * Reglas de negocio del inventario.
 */
final class InventarioService
{
    public function __construct(private readonly ProductoRepository $repo)
    {
    }

    /** @param array<string,mixed> $d Datos ya validados. */
    public function guardar(?int $id, array $d): array
    {
        $datos = [
            'nombre' => trim((string)$d['nombre']),
            'categoria' => trim((string)$d['categoria']),
            'tipo' => self::opcional($d, 'tipo'),
            'descripcion' => self::opcional($d, 'descripcion'),
            'stock_minimo' => (int)$d['stock_minimo'],
            'precio_referencia' => ($d['precio_referencia'] ?? '') !== '' ? (float)$d['precio_referencia'] : null,
        ];

        if ($id === null) {
            $id = $this->repo->create($datos + ['cantidad_disponible' => (int)$d['cantidad_disponible']]);
        } else {
            // La cantidad disponible solo cambia por el endpoint de stock.
            $this->existente($id);
            $this->repo->update($id, $datos);
        }
        return $this->conIndicador((array)$this->repo->find($id));
    }

    public function actualizarStock(int $id, int $cantidad): array
    {
        $producto = $this->existente($id);
        if ($producto['estado'] !== 'activo') {
            throw HttpException::conflict('No es posible actualizar el stock de un producto inactivo.');
        }
        $this->repo->actualizarCantidad($id, max(0, $cantidad));
        return $this->conIndicador((array)$this->repo->find($id));
    }

    public function desactivar(int $id): array
    {
        $this->existente($id);
        $this->repo->desactivar($id);
        return (array)$this->repo->find($id);
    }

    /** @return array{total:int,stock_bajo:int} */
    public function resumen(): array
    {
        return ['total' => $this->repo->contarActivos(), 'stock_bajo' => count($this->repo->stockBajo())];
    }

    public static function esStockBajo(array $p): bool
    {
        return (int)$p['cantidad_disponible'] <= (int)$p['stock_minimo'];
    }

    private function existente(int $id): array
    {
        return $this->repo->find($id) ?? throw HttpException::notFound('Producto no encontrado');
    }

    private function conIndicador(array $p): array
    {
        return $p + ['stock_bajo' => self::esStockBajo($p)];
    }

    /** @param array<string,mixed> $d */
    private static function opcional(array $d, string $campo): ?string
    {
        $v = trim((string)($d[$campo] ?? ''));
        return $v === '' ? null : $v;
    }
}
