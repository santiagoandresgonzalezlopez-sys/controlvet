<?php
/** Ruta: controlvet/models/Usuario.php — Modelo de datos: usuarios */

class Usuario
{
    public static function findByEmail(string $email): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT u.*, r.nombre AS rol_nombre FROM usuarios u
             JOIN roles r ON r.id = u.rol_id
             WHERE u.email = ? AND u.estado = "activo"'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT u.*, r.nombre AS rol_nombre FROM usuarios u
             JOIN roles r ON r.id = u.rol_id WHERE u.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function all(): array
    {
        $db = Database::getConnection();
        return $db->query(
            'SELECT u.*, r.nombre AS rol_nombre FROM usuarios u
             JOIN roles r ON r.id = u.rol_id ORDER BY u.id DESC'
        )->fetchAll();
    }

    public static function emailExists(string $email, ?int $excludeId = null): bool
    {
        $db = Database::getConnection();
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE email = ?';
        $params = [$email];
        if ($excludeId) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function create(array $data): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO usuarios (nombre, apellido, email, password_hash, rol_id, propietario_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['nombre'], $data['apellido'], $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['rol_id'], $data['propietario_id'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $db = Database::getConnection();
        if (!empty($data['password'])) {
            $stmt = $db->prepare(
                'UPDATE usuarios SET nombre=?, apellido=?, email=?, rol_id=?, password_hash=? WHERE id=?'
            );
            $stmt->execute([
                $data['nombre'], $data['apellido'], $data['email'], $data['rol_id'],
                password_hash($data['password'], PASSWORD_BCRYPT), $id,
            ]);
        } else {
            $stmt = $db->prepare('UPDATE usuarios SET nombre=?, apellido=?, email=?, rol_id=? WHERE id=?');
            $stmt->execute([$data['nombre'], $data['apellido'], $data['email'], $data['rol_id'], $id]);
        }
    }

    public static function setEstado(int $id, string $estado): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE usuarios SET estado=? WHERE id=?');
        $stmt->execute([$estado, $id]);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
