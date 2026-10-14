<?php
/** Ruta: controlvet/models/Propietario.php */

class Propietario
{
    public static function all(string $buscar = ''): array
    {
        $db = Database::getConnection();
        if ($buscar !== '') {
            $stmt = $db->prepare(
                "SELECT * FROM propietarios WHERE estado='activo' AND
                 (nombre LIKE ? OR apellido LIKE ? OR documento LIKE ?) ORDER BY apellido"
            );
            $like = "%$buscar%";
            $stmt->execute([$like, $like, $like]);
            return $stmt->fetchAll();
        }
        return $db->query("SELECT * FROM propietarios WHERE estado='activo' ORDER BY apellido")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM propietarios WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function documentoExists(string $documento, ?int $excludeId = null): bool
    {
        $db = Database::getConnection();
        $sql = 'SELECT COUNT(*) FROM propietarios WHERE documento=?';
        $params = [$documento];
        if ($excludeId) { $sql .= ' AND id<>?'; $params[] = $excludeId; }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function create(array $d): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO propietarios (nombre, apellido, documento, telefono, email, direccion) VALUES (?,?,?,?,?,?)'
        );
        $stmt->execute([$d['nombre'], $d['apellido'], $d['documento'], $d['telefono'], $d['email'] ?: null, $d['direccion'] ?: null]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE propietarios SET nombre=?, apellido=?, documento=?, telefono=?, email=?, direccion=? WHERE id=?'
        );
        $stmt->execute([$d['nombre'], $d['apellido'], $d['documento'], $d['telefono'], $d['email'] ?: null, $d['direccion'] ?: null, $id]);
    }

    /** Desactivación lógica; nunca eliminación física. */
    public static function desactivar(int $id): bool
    {
        $mascotasActivas = Database::getConnection()->prepare(
            "SELECT COUNT(*) FROM mascotas WHERE propietario_id=? AND estado='activo'"
        );
        $mascotasActivas->execute([$id]);
        if ((int)$mascotasActivas->fetchColumn() > 0) {
            return false; // No se permite si tiene mascotas activas asociadas.
        }
        $stmt = Database::getConnection()->prepare("UPDATE propietarios SET estado='inactivo' WHERE id=?");
        $stmt->execute([$id]);
        return true;
    }

    public static function mascotas(int $id): array
    {
        $stmt = Database::getConnection()->prepare("SELECT * FROM mascotas WHERE propietario_id=? AND estado='activo'");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public static function count(): int
    {
        return (int)Database::getConnection()->query("SELECT COUNT(*) FROM propietarios WHERE estado='activo'")->fetchColumn();
    }
}
