<?php
/** Ruta: controlvet/web/models/Producto.php — Cliente del microservicio inventario */

class Producto
{
    public static function all(string $buscar = ''): array
    {
        return Api::get('inventario', '/productos', ['buscar' => $buscar]) ?? [];
    }

    public static function find(int $id): ?array
    {
        return Api::get('inventario', '/productos/' . $id);
    }

    public static function stockBajo(): array
    {
        return Api::get('inventario', '/productos/stock-bajo') ?? [];
    }

    public static function countStockBajo(): int
    {
        return (int)(Api::get('inventario', '/resumen')['stock_bajo'] ?? 0);
    }

    public static function count(): int
    {
        return (int)(Api::get('inventario', '/resumen')['total'] ?? 0);
    }

    public static function create(array $d): int
    {
        return (int)Api::post('inventario', '/productos', self::payload($d))['id'];
    }

    public static function update(int $id, array $d): void
    {
        Api::put('inventario', '/productos/' . $id, self::payload($d));
    }

    public static function actualizarCantidad(int $id, int $nuevaCantidad): array
    {
        return Api::patch('inventario', '/productos/' . $id . '/stock', ['cantidad_disponible' => $nuevaCantidad]);
    }

    public static function desactivar(int $id): void
    {
        Api::patch('inventario', '/productos/' . $id . '/desactivar');
    }

    private static function payload(array $d): array
    {
        return array_intersect_key($d, array_flip([
            'nombre', 'categoria', 'tipo', 'descripcion', 'cantidad_disponible', 'stock_minimo', 'precio_referencia',
        ]));
    }
}
