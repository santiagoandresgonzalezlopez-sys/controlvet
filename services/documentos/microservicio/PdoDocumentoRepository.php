<?php

declare(strict_types=1);

namespace ControlVet\Documentos\Microservicio;

use PDO;

final class PdoDocumentoRepository implements DocumentoRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function registrar(string $tipo, int $referenciaId, string $nombreArchivo, int $generadoPor, int $propietarioId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO documentos (tipo, referencia_id, nombre_archivo, generado_por, propietario_id) VALUES (?,?,?,?,?)'
        );
        $stmt->execute([$tipo, $referenciaId, $nombreArchivo, $generadoPor, $propietarioId]);
        return (int)$this->db->lastInsertId();
    }

    public function todos(): array
    {
        return $this->db->query('SELECT * FROM documentos ORDER BY fecha_generacion DESC, id DESC')->fetchAll();
    }

    public function porPropietario(int $propietarioId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM documentos WHERE propietario_id = ? ORDER BY fecha_generacion DESC, id DESC');
        $stmt->execute([$propietarioId]);
        return $stmt->fetchAll();
    }
}
