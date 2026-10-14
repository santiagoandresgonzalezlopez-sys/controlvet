<?php
/** Ruta: controlvet/models/Producto.php — Inventario */

class Producto
{
    public static function all(string $buscar = ''): array
    {
        $db = Database::getConnection();
        $sql = "SELECT * FROM productos WHERE estado='activo'";
        $params = [];
        if ($buscar !== '') {
            $sql .= ' AND (nombre LIKE ? OR categoria LIKE ?)';
            $params = ["%$buscar%", "%$buscar%"];
        }
        $sql .= ' ORDER BY nombre';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM productos WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function stockBajo(): array
    {
        return Database::getConnection()->query(
            "SELECT * FROM productos WHERE estado='activo' AND cantidad_disponible <= stock_minimo ORDER BY nombre"
        )->fetchAll();
    }

    public static function countStockBajo(): int
    {
        return (int)Database::getConnection()->query(
            "SELECT COUNT(*) FROM productos WHERE estado='activo' AND cantidad_disponible <= stock_minimo"
        )->fetchColumn();
    }

    public static function count(): int
    {
        return (int)Database::getConnection()->query("SELECT COUNT(*) FROM productos WHERE estado='activo'")->fetchColumn();
    }

    public static function create(array $d): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO productos (nombre, categoria, tipo, descripcion, cantidad_disponible, stock_minimo, precio_referencia)
             VALUES (?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $d['nombre'], $d['categoria'], $d['tipo'] ?: null, $d['descripcion'] ?: null,
            $d['cantidad_disponible'], $d['stock_minimo'], $d['precio_referencia'] ?: null,
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE productos SET nombre=?, categoria=?, tipo=?, descripcion=?, stock_minimo=?, precio_referencia=? WHERE id=?'
        );
        $stmt->execute([$d['nombre'], $d['categoria'], $d['tipo'] ?: null, $d['descripcion'] ?: null, $d['stock_minimo'], $d['precio_referencia'] ?: null, $id]);
    }

    public static function actualizarCantidad(int $id, int $nuevaCantidad): void
    {
        $stmt = Database::getConnection()->prepare('UPDATE productos SET cantidad_disponible=? WHERE id=?');
        $stmt->execute([$nuevaCantidad, $id]);
    }

    public static function desactivar(int $id): void
    {
        $stmt = Database::getConnection()->prepare("UPDATE productos SET estado='inactivo' WHERE id=?");
        $stmt->execute([$id]);
    }
}
