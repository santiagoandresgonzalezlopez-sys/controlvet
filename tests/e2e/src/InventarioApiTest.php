<?php

declare(strict_types=1);

namespace ControlVet\E2E;

use PHPUnit\Framework\TestCase;

final class InventarioApiTest extends TestCase
{
    public function testCrearProductoActualizarStockYDesactivar(): void
    {
        $admin = Http::token('administrador');

        $p = Http::api('POST', 'inventario', '/productos', $admin, [
            'nombre' => 'Producto E2E ' . random_int(1, 99999), 'categoria' => 'Insumo', 'cantidad_disponible' => 2, 'stock_minimo' => 5,
        ]);
        self::assertSame(201, $p['status']);
        self::assertTrue($p['json']['stock_bajo']);
        $id = $p['json']['id'];

        $stockBajo = array_column(Http::api('GET', 'inventario', '/productos/stock-bajo', $admin)['json'], 'id');
        self::assertContains($id, $stockBajo);

        $s = Http::api('PATCH', 'inventario', "/productos/$id/stock", $admin, ['cantidad_disponible' => 50]);
        self::assertSame(50, $s['json']['cantidad_disponible']);
        self::assertFalse($s['json']['stock_bajo']);

        self::assertSame('inactivo', Http::api('PATCH', 'inventario', "/productos/$id/desactivar", $admin, [])['json']['estado']);
        self::assertSame(409, Http::api('PATCH', 'inventario', "/productos/$id/stock", $admin, ['cantidad_disponible' => 1])['status']);
    }

    public function testCantidadNegativaEsRechazada(): void
    {
        $r = Http::api('PATCH', 'inventario', '/productos/1/stock', Http::token('administrador'), ['cantidad_disponible' => -4]);
        self::assertSame(422, $r['status']);
    }
}
