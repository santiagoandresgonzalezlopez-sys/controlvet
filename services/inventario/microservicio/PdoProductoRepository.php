<?php

declare(strict_types=1);

namespace ControlVet\Inventario\Microservicio;

use PDO;

final class PdoProductoRepository implements ProductoRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function activos(string $buscar): array
    {
        $sql = "SELECT * FROM productos WHERE estado='activo'";
        $params = [];
        if ($buscar !== '') {
            $sql .= ' AND (nombre LIKE ? OR categoria LIKE ?)';
            $params = ["%$buscar%", "%$buscar%"];
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY nombre');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function stockBajo(): array
    {
        return $this->db->query(
            "SELECT * FROM productos WHERE estado='activo' AND cantidad_disponible <= stock_minimo ORDER BY nombre"
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM productos WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO productos (nombre, categoria, tipo, descripcion, cantidad_disponible, stock_minimo, precio_referencia)
             VALUES (?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $d['nombre'], $d['categoria'], $d['tipo'], $d['descripcion'],
            $d['cantidad_disponible'], $d['stock_minimo'], $d['precio_referencia'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): void
    {
        $stmt = $this->db->prepare(
            'UPDATE productos SET nombre=?, categoria=?, tipo=?, descripcion=?, stock_minimo=?, precio_referencia=? WHERE id=?'
        );
        $stmt->execute([$d['nombre'], $d['categoria'], $d['tipo'], $d['descripcion'], $d['stock_minimo'], $d['precio_referencia'], $id]);
    }

    public function actualizarCantidad(int $id, int $cantidad): void
    {
        $this->db->prepare('UPDATE productos SET cantidad_disponible=? WHERE id=?')->execute([$cantidad, $id]);
    }

    public function desactivar(int $id): void
    {
        $this->db->prepare("UPDATE productos SET estado='inactivo' WHERE id=?")->execute([$id]);
    }

    public function contarActivos(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM productos WHERE estado='activo'")->fetchColumn();
    }
}
