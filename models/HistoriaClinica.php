<?php
/** Ruta: controlvet/models/HistoriaClinica.php — histórico clínico inmutable */

class HistoriaClinica
{
    private const SELECT_BASE = "
        SELECT h.*, m.nombre AS mascota_nombre, m.especie, m.propietario_id,
               p.nombre AS propietario_nombre, p.apellido AS propietario_apellido,
               u.nombre AS vet_nombre, u.apellido AS vet_apellido
        FROM historias_clinicas h
        JOIN mascotas m ON m.id = h.mascota_id
        JOIN propietarios p ON p.id = m.propietario_id
        JOIN usuarios u ON u.id = h.veterinario_id
    ";

    public static function porMascota(int $mascotaId): array
    {
        $stmt = Database::getConnection()->prepare(self::SELECT_BASE . ' WHERE h.mascota_id = ? ORDER BY h.fecha DESC');
        $stmt->execute([$mascotaId]);
        return $stmt->fetchAll();
    }

    public static function porPropietario(int $propietarioId): array
    {
        $stmt = Database::getConnection()->prepare(self::SELECT_BASE . ' WHERE p.id = ? ORDER BY h.fecha DESC');
        $stmt->execute([$propietarioId]);
        return $stmt->fetchAll();
    }

    public static function recientes(int $limite = 5, ?int $veterinarioId = null): array
    {
        $sql = self::SELECT_BASE . ' WHERE 1=1';
        $params = [];
        if ($veterinarioId) { $sql .= ' AND h.veterinario_id = ?'; $params[] = $veterinarioId; }
        $sql .= ' ORDER BY h.fecha DESC LIMIT ' . (int)$limite;
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare(self::SELECT_BASE . ' WHERE h.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Registro único e inmutable: no existe método de actualización a propósito. */
    public static function create(array $d): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO historias_clinicas (mascota_id, veterinario_id, cita_id, motivo_consulta, peso, sintomas, diagnostico, tratamiento, observaciones)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $d['mascota_id'], $d['veterinario_id'], $d['cita_id'] ?: null, $d['motivo_consulta'],
            $d['peso'] ?: null, $d['sintomas'] ?: null, $d['diagnostico'], $d['tratamiento'] ?: null, $d['observaciones'] ?: null,
        ]);

        // Se captura el ID INMEDIATAMENTE tras el INSERT: una vez se ejecute
        // el UPDATE de más abajo (que no genera valor AUTO_INCREMENT),
        // lastInsertId() se reinicia a 0 en MySQL/PDO.
        $nuevoId = (int)$db->lastInsertId();

        // Actualiza el peso actual de la mascota con el último registrado.
        if (!empty($d['peso'])) {
            $upd = $db->prepare('UPDATE mascotas SET peso_actual=? WHERE id=?');
            $upd->execute([$d['peso'], $d['mascota_id']]);
        }

        return $nuevoId;
    }

    public static function crearFormula(int $historiaId, array $d): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO formulas_medicas (historia_id, medicamento, dosis, frecuencia, duracion, indicaciones) VALUES (?,?,?,?,?,?)'
        );
        $stmt->execute([$historiaId, $d['medicamento'], $d['dosis'], $d['frecuencia'], $d['duracion'], $d['indicaciones'] ?: null]);
        return (int)$db->lastInsertId();
    }

    public static function findFormula(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT f.*, h.mascota_id, h.veterinario_id FROM formulas_medicas f
             JOIN historias_clinicas h ON h.id = f.historia_id WHERE f.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function formulasPorHistoria(int $historiaId): array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM formulas_medicas WHERE historia_id = ? ORDER BY id DESC');
        $stmt->execute([$historiaId]);
        return $stmt->fetchAll();
    }
}
