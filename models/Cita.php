<?php
/** Ruta: controlvet/models/Cita.php */

class Cita
{
    private const SELECT_BASE = "
        SELECT c.*, m.nombre AS mascota_nombre, p.id AS propietario_id,
               p.nombre AS propietario_nombre, p.apellido AS propietario_apellido,
               u.nombre AS vet_nombre, u.apellido AS vet_apellido
        FROM citas c
        JOIN mascotas m ON m.id = c.mascota_id
        JOIN propietarios p ON p.id = m.propietario_id
        JOIN usuarios u ON u.id = c.veterinario_id
    ";

    public static function all(array $filtros = []): array
    {
        $sql = self::SELECT_BASE . ' WHERE 1=1';
        $params = [];

        if (!empty($filtros['fecha'])) { $sql .= ' AND c.fecha = ?'; $params[] = $filtros['fecha']; }
        if (!empty($filtros['veterinario_id'])) { $sql .= ' AND c.veterinario_id = ?'; $params[] = $filtros['veterinario_id']; }
        if (!empty($filtros['estado'])) { $sql .= ' AND c.estado = ?'; $params[] = $filtros['estado']; }
        if (!empty($filtros['propietario_id'])) { $sql .= ' AND p.id = ?'; $params[] = $filtros['propietario_id']; }

        $sql .= ' ORDER BY c.fecha DESC, c.hora DESC';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function delDia(?int $veterinarioId = null): array
    {
        $filtros = ['fecha' => date('Y-m-d')];
        if ($veterinarioId) { $filtros['veterinario_id'] = $veterinarioId; }
        return self::all($filtros);
    }

    public static function proximas(int $limite = 5, ?int $veterinarioId = null, ?int $propietarioId = null): array
    {
        $sql = self::SELECT_BASE . " WHERE c.fecha >= CURDATE() AND c.estado <> 'cancelada'";
        $params = [];
        if ($veterinarioId) { $sql .= ' AND c.veterinario_id = ?'; $params[] = $veterinarioId; }
        if ($propietarioId) { $sql .= ' AND p.id = ?'; $params[] = $propietarioId; }
        $sql .= ' ORDER BY c.fecha ASC, c.hora ASC LIMIT ' . (int)$limite;
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare(self::SELECT_BASE . ' WHERE c.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $d): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO citas (mascota_id, veterinario_id, fecha, hora, motivo, observaciones, creado_por)
             VALUES (?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $d['mascota_id'], $d['veterinario_id'], $d['fecha'], $d['hora'],
            $d['motivo'], $d['observaciones'] ?: null, $d['creado_por'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE citas SET mascota_id=?, veterinario_id=?, fecha=?, hora=?, motivo=?, observaciones=? WHERE id=?'
        );
        $stmt->execute([$d['mascota_id'], $d['veterinario_id'], $d['fecha'], $d['hora'], $d['motivo'], $d['observaciones'] ?: null, $id]);
    }

    public static function cambiarEstado(int $id, string $estado): void
    {
        $stmt = Database::getConnection()->prepare('UPDATE citas SET estado=? WHERE id=?');
        $stmt->execute([$estado, $id]);
    }

    public static function veterinarios(): array
    {
        return Database::getConnection()->query(
            "SELECT u.id, u.nombre, u.apellido FROM usuarios u JOIN roles r ON r.id=u.rol_id
             WHERE r.nombre='veterinario' AND u.estado='activo'"
        )->fetchAll();
    }
}
