<?php

declare(strict_types=1);

namespace ControlVet\Citas\Microservicio;

use PDO;

final class PdoCitaRepository implements CitaRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function buscar(array $filtros): array
    {
        $sql = 'SELECT * FROM citas WHERE 1=1';
        $params = [];
        foreach (['fecha', 'veterinario_id', 'estado', 'propietario_id'] as $campo) {
            if (isset($filtros[$campo]) && $filtros[$campo] !== '') {
                $sql .= " AND $campo = ?";
                $params[] = $filtros[$campo];
            }
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY fecha DESC, hora DESC');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function proximas(int $limite, ?int $veterinarioId, ?int $propietarioId): array
    {
        $sql = "SELECT * FROM citas WHERE fecha >= CURDATE() AND estado <> 'cancelada'";
        $params = [];
        if ($veterinarioId) {
            $sql .= ' AND veterinario_id = ?';
            $params[] = $veterinarioId;
        }
        if ($propietarioId) {
            $sql .= ' AND propietario_id = ?';
            $params[] = $propietarioId;
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY fecha ASC, hora ASC LIMIT ' . max(1, min(100, $limite)));
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM citas WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function horarioOcupado(int $veterinarioId, string $fecha, string $hora, ?int $excluirId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM citas WHERE veterinario_id = ? AND fecha = ? AND hora = ? AND estado <> 'cancelada' AND id <> ?"
        );
        $stmt->execute([$veterinarioId, $fecha, $hora, $excluirId ?? 0]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO citas (mascota_id, propietario_id, veterinario_id, fecha, hora, motivo, observaciones, creado_por)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $d['mascota_id'], $d['propietario_id'], $d['veterinario_id'], $d['fecha'], $d['hora'],
            $d['motivo'], ($d['observaciones'] ?? '') ?: null, $d['creado_por'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): void
    {
        $stmt = $this->db->prepare(
            'UPDATE citas SET mascota_id=?, propietario_id=?, veterinario_id=?, fecha=?, hora=?, motivo=?, observaciones=? WHERE id=?'
        );
        $stmt->execute([
            $d['mascota_id'], $d['propietario_id'], $d['veterinario_id'], $d['fecha'], $d['hora'],
            $d['motivo'], ($d['observaciones'] ?? '') ?: null, $id,
        ]);
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $this->db->prepare('UPDATE citas SET estado=? WHERE id=?')->execute([$estado, $id]);
    }
}
