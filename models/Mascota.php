<?php
/** Ruta: controlvet/models/Mascota.php */

class Mascota
{
    public static function all(string $buscar = ''): array
    {
        $db = Database::getConnection();
        $sql = "SELECT m.*, p.nombre AS propietario_nombre, p.apellido AS propietario_apellido
                FROM mascotas m JOIN propietarios p ON p.id = m.propietario_id
                WHERE m.estado='activo'";
        $params = [];
        if ($buscar !== '') {
            $sql .= ' AND (m.nombre LIKE ? OR p.nombre LIKE ? OR p.apellido LIKE ?)';
            $like = "%$buscar%";
            $params = [$like, $like, $like];
        }
        $sql .= ' ORDER BY m.nombre';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT m.*, p.nombre AS propietario_nombre, p.apellido AS propietario_apellido, p.telefono AS propietario_telefono
             FROM mascotas m JOIN propietarios p ON p.id = m.propietario_id WHERE m.id=?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function porPropietario(int $propietarioId): array
    {
        $stmt = Database::getConnection()->prepare("SELECT * FROM mascotas WHERE propietario_id=? AND estado='activo'");
        $stmt->execute([$propietarioId]);
        return $stmt->fetchAll();
    }

    public static function create(array $d): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO mascotas (nombre, especie, raza, sexo, fecha_nacimiento, peso_actual, color, propietario_id, observaciones)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $d['nombre'], $d['especie'], $d['raza'] ?: null, $d['sexo'],
            $d['fecha_nacimiento'] ?: null, $d['peso_actual'] ?: null, $d['color'] ?: null,
            $d['propietario_id'], $d['observaciones'] ?: null,
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE mascotas SET nombre=?, especie=?, raza=?, sexo=?, fecha_nacimiento=?, peso_actual=?, color=?, propietario_id=?, observaciones=? WHERE id=?'
        );
        $stmt->execute([
            $d['nombre'], $d['especie'], $d['raza'] ?: null, $d['sexo'],
            $d['fecha_nacimiento'] ?: null, $d['peso_actual'] ?: null, $d['color'] ?: null,
            $d['propietario_id'], $d['observaciones'] ?: null, $id,
        ]);
    }

    public static function count(): int
    {
        return (int)Database::getConnection()->query("SELECT COUNT(*) FROM mascotas WHERE estado='activo'")->fetchColumn();
    }

    public static function countPorPropietario(int $propietarioId): int
    {
        $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM mascotas WHERE propietario_id=? AND estado='activo'");
        $stmt->execute([$propietarioId]);
        return (int)$stmt->fetchColumn();
    }
}
