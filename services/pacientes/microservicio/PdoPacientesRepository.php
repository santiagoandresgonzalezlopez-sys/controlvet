<?php

declare(strict_types=1);

namespace ControlVet\Pacientes\Microservicio;

use PDO;

final class PdoPacientesRepository implements PacientesRepository
{
    private const MASCOTA_SELECT = "SELECT m.*, p.nombre AS propietario_nombre, p.apellido AS propietario_apellido,
                                           p.telefono AS propietario_telefono
                                    FROM mascotas m JOIN propietarios p ON p.id = m.propietario_id";

    public function __construct(private readonly PDO $db)
    {
    }

    public function propietarios(string $buscar): array
    {
        if ($buscar !== '') {
            $stmt = $this->db->prepare(
                "SELECT * FROM propietarios WHERE estado='activo' AND
                 (nombre LIKE ? OR apellido LIKE ? OR documento LIKE ?) ORDER BY apellido"
            );
            $like = "%$buscar%";
            $stmt->execute([$like, $like, $like]);
            return $stmt->fetchAll();
        }
        return $this->db->query("SELECT * FROM propietarios WHERE estado='activo' ORDER BY apellido")->fetchAll();
    }

    public function propietario(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM propietarios WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function documentoExists(string $documento, ?int $excludeId): bool
    {
        $sql = 'SELECT COUNT(*) FROM propietarios WHERE documento=?';
        $params = [$documento];
        if ($excludeId) {
            $sql .= ' AND id<>?';
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function crearPropietario(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO propietarios (nombre, apellido, documento, telefono, email, direccion) VALUES (?,?,?,?,?,?)'
        );
        $stmt->execute([$d['nombre'], $d['apellido'], $d['documento'], $d['telefono'], $d['email'] ?: null, $d['direccion'] ?: null]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizarPropietario(int $id, array $d): void
    {
        $stmt = $this->db->prepare(
            'UPDATE propietarios SET nombre=?, apellido=?, documento=?, telefono=?, email=?, direccion=? WHERE id=?'
        );
        $stmt->execute([$d['nombre'], $d['apellido'], $d['documento'], $d['telefono'], $d['email'] ?: null, $d['direccion'] ?: null, $id]);
    }

    public function desactivarPropietario(int $id): void
    {
        $this->db->prepare("UPDATE propietarios SET estado='inactivo' WHERE id=?")->execute([$id]);
    }

    public function contarMascotasActivas(int $propietarioId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM mascotas WHERE propietario_id=? AND estado='activo'");
        $stmt->execute([$propietarioId]);
        return (int)$stmt->fetchColumn();
    }

    public function contarPropietarios(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM propietarios WHERE estado='activo'")->fetchColumn();
    }

    public function mascotas(string $buscar, ?int $propietarioId, array $ids): array
    {
        $where = [];
        $params = [];
        if ($ids) {
            // La consulta por ids incluye mascotas inactivas: se usa para
            // componer histórico (citas/historias) de pacientes ya retirados.
            $where[] = 'm.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            array_push($params, ...$ids);
        } else {
            $where[] = "m.estado='activo'";
        }
        if ($buscar !== '') {
            $where[] = '(m.nombre LIKE ? OR p.nombre LIKE ? OR p.apellido LIKE ?)';
            $like = "%$buscar%";
            array_push($params, $like, $like, $like);
        }
        if ($propietarioId !== null) {
            $where[] = 'm.propietario_id = ?';
            $params[] = $propietarioId;
        }
        $stmt = $this->db->prepare(self::MASCOTA_SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY m.nombre');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function mascota(int $id): ?array
    {
        $stmt = $this->db->prepare(self::MASCOTA_SELECT . ' WHERE m.id=?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function crearMascota(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO mascotas (nombre, especie, raza, sexo, fecha_nacimiento, peso_actual, color, propietario_id, observaciones)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute($this->mascotaParams($d));
        return (int)$this->db->lastInsertId();
    }

    public function actualizarMascota(int $id, array $d): void
    {
        $stmt = $this->db->prepare(
            'UPDATE mascotas SET nombre=?, especie=?, raza=?, sexo=?, fecha_nacimiento=?, peso_actual=?, color=?, propietario_id=?, observaciones=? WHERE id=?'
        );
        $stmt->execute([...$this->mascotaParams($d), $id]);
    }

    public function actualizarPeso(int $id, float $peso): void
    {
        $this->db->prepare('UPDATE mascotas SET peso_actual=? WHERE id=?')->execute([$peso, $id]);
    }

    public function contarMascotas(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM mascotas WHERE estado='activo'")->fetchColumn();
    }

    /** @param array<string,mixed> $d */
    private function mascotaParams(array $d): array
    {
        return [
            $d['nombre'], $d['especie'], ($d['raza'] ?? '') ?: null, $d['sexo'],
            ($d['fecha_nacimiento'] ?? '') ?: null, ($d['peso_actual'] ?? '') ?: null, ($d['color'] ?? '') ?: null,
            (int)$d['propietario_id'], ($d['observaciones'] ?? '') ?: null,
        ];
    }
}
