<?php

declare(strict_types=1);

namespace ControlVet\Auth\Microservicio;

use PDO;

final class PdoUsuarioRepository implements UsuarioRepository
{
    private const SELECT = 'SELECT u.id, u.nombre, u.apellido, u.email, u.rol_id, u.propietario_id,
                                   u.fecha_creacion, u.fecha_actualizacion, u.estado, r.nombre AS rol_nombre
                            FROM usuarios u JOIN roles r ON r.id = u.rol_id';

    public function __construct(private readonly PDO $db)
    {
    }

    public function findActiveByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.nombre AS rol_nombre FROM usuarios u JOIN roles r ON r.id = u.rol_id
             WHERE u.email = ? AND u.estado = 'activo'"
        );
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE u.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function all(): array
    {
        return $this->db->query(self::SELECT . ' ORDER BY u.id DESC')->fetchAll();
    }

    public function veterinarios(bool $incluirInactivos): array
    {
        $sql = "SELECT u.id, u.nombre, u.apellido, u.estado FROM usuarios u JOIN roles r ON r.id = u.rol_id
                WHERE r.nombre = 'veterinario'" . ($incluirInactivos ? '' : " AND u.estado = 'activo'") . ' ORDER BY u.id';
        return $this->db->query($sql)->fetchAll();
    }

    public function roles(): array
    {
        return $this->db->query('SELECT id, nombre FROM roles ORDER BY id')->fetchAll();
    }

    public function rolExists(int $rolId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM roles WHERE id = ?');
        $stmt->execute([$rolId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE email = ?';
        $params = [$email];
        if ($excludeId) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nombre, apellido, email, password_hash, rol_id, propietario_id) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['nombre'], $data['apellido'], $data['email'], $data['password_hash'],
            $data['rol_id'], $data['propietario_id'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        if (!empty($data['password_hash'])) {
            $stmt = $this->db->prepare('UPDATE usuarios SET nombre=?, apellido=?, email=?, rol_id=?, password_hash=? WHERE id=?');
            $stmt->execute([$data['nombre'], $data['apellido'], $data['email'], $data['rol_id'], $data['password_hash'], $id]);
            return;
        }
        $stmt = $this->db->prepare('UPDATE usuarios SET nombre=?, apellido=?, email=?, rol_id=? WHERE id=?');
        $stmt->execute([$data['nombre'], $data['apellido'], $data['email'], $data['rol_id'], $id]);
    }

    public function setEstado(int $id, string $estado): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET estado=? WHERE id=?');
        $stmt->execute([$estado, $id]);
    }
}
