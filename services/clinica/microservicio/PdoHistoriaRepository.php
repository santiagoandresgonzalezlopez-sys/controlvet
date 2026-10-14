<?php

declare(strict_types=1);

namespace ControlVet\Clinica\Microservicio;

use PDO;

final class PdoHistoriaRepository implements HistoriaRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function buscar(array $filtros, int $limite): array
    {
        $sql = 'SELECT * FROM historias_clinicas WHERE 1=1';
        $params = [];
        foreach (['mascota_id', 'propietario_id', 'veterinario_id'] as $campo) {
            if (isset($filtros[$campo])) {
                $sql .= " AND $campo = ?";
                $params[] = $filtros[$campo];
            }
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY fecha DESC, id DESC LIMIT ' . max(1, min(500, $limite)));
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM historias_clinicas WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO historias_clinicas (mascota_id, propietario_id, veterinario_id, cita_id, motivo_consulta, peso, sintomas, diagnostico, tratamiento, observaciones)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $d['mascota_id'], $d['propietario_id'], $d['veterinario_id'], $d['cita_id'], $d['motivo_consulta'],
            $d['peso'], $d['sintomas'], $d['diagnostico'], $d['tratamiento'], $d['observaciones'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function formulas(int $historiaId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM formulas_medicas WHERE historia_id = ? ORDER BY id DESC');
        $stmt->execute([$historiaId]);
        return $stmt->fetchAll();
    }

    public function formula(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT f.*, h.mascota_id, h.veterinario_id, h.propietario_id FROM formulas_medicas f
             JOIN historias_clinicas h ON h.id = f.historia_id WHERE f.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function createFormula(int $historiaId, array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO formulas_medicas (historia_id, medicamento, dosis, frecuencia, duracion, indicaciones) VALUES (?,?,?,?,?,?)'
        );
        $stmt->execute([$historiaId, $d['medicamento'], $d['dosis'], $d['frecuencia'], $d['duracion'], $d['indicaciones']]);
        return (int)$this->db->lastInsertId();
    }
}
