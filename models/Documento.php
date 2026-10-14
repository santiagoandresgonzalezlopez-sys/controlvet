<?php
/** Ruta: controlvet/models/Documento.php — Registro de documentos PDF generados */

class Documento
{
    public static function registrar(string $tipo, int $referenciaId, string $nombreArchivo, ?int $generadoPor): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO documentos (tipo, referencia_id, nombre_archivo, generado_por) VALUES (?,?,?,?)'
        );
        $stmt->execute([$tipo, $referenciaId, $nombreArchivo, $generadoPor]);
        return (int)$db->lastInsertId();
    }

    public static function porPropietario(int $propietarioId): array
    {
        // Documentos relacionados con las mascotas del propietario (citas, resúmenes y fórmulas).
        $stmt = Database::getConnection()->prepare("
            SELECT d.* FROM documentos d WHERE
            (d.tipo='comprobante_cita' AND d.referencia_id IN
                (SELECT c.id FROM citas c JOIN mascotas m ON m.id=c.mascota_id WHERE m.propietario_id=?))
            OR (d.tipo='resumen_consulta' AND d.referencia_id IN
                (SELECT h.id FROM historias_clinicas h JOIN mascotas m ON m.id=h.mascota_id WHERE m.propietario_id=?))
            OR (d.tipo='formula_medica' AND d.referencia_id IN
                (SELECT f.id FROM formulas_medicas f JOIN historias_clinicas h ON h.id=f.historia_id
                 JOIN mascotas m ON m.id=h.mascota_id WHERE m.propietario_id=?))
            ORDER BY d.fecha_generacion DESC
        ");
        $stmt->execute([$propietarioId, $propietarioId, $propietarioId]);
        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        return Database::getConnection()->query('SELECT * FROM documentos ORDER BY fecha_generacion DESC')->fetchAll();
    }
}
