<?php

declare(strict_types=1);

namespace ControlVet\Auth\Tests;

use ControlVet\Auth\Microservicio\UsuarioRepository;

/** Doble de prueba en memoria: permite probar las reglas de negocio sin base de datos. */
final class InMemoryUsuarioRepository implements UsuarioRepository
{
    private const ROLES = [1 => 'administrador', 2 => 'recepcionista', 3 => 'veterinario', 4 => 'cliente'];

    /** @var array<int,array<string,mixed>> */
    public array $usuarios = [];

    /** @param array<string,mixed> $u */
    public function seed(array $u): void
    {
        $u += ['estado' => 'activo', 'propietario_id' => null];
        $u['rol_nombre'] = self::ROLES[$u['rol_id']];
        $this->usuarios[$u['id']] = $u;
    }

    public function findActiveByEmail(string $email): ?array
    {
        foreach ($this->usuarios as $u) {
            if ($u['email'] === $email && $u['estado'] === 'activo') {
                return $u;
            }
        }
        return null;
    }

    public function findById(int $id): ?array
    {
        $u = $this->usuarios[$id] ?? null;
        if ($u) {
            unset($u['password_hash']);
        }
        return $u;
    }

    public function all(): array
    {
        return array_values(array_map(fn($u) => $this->findById($u['id']), $this->usuarios));
    }

    public function veterinarios(bool $incluirInactivos): array
    {
        return array_values(array_filter($this->usuarios, fn($u) => $u['rol_id'] === 3 && ($incluirInactivos || $u['estado'] === 'activo')));
    }

    public function roles(): array
    {
        return array_map(fn($id, $n) => ['id' => $id, 'nombre' => $n], array_keys(self::ROLES), self::ROLES);
    }

    public function rolExists(int $rolId): bool
    {
        return isset(self::ROLES[$rolId]);
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        foreach ($this->usuarios as $u) {
            if ($u['email'] === $email && $u['id'] !== $excludeId) {
                return true;
            }
        }
        return false;
    }

    public function create(array $data): int
    {
        $id = $this->usuarios ? max(array_keys($this->usuarios)) + 1 : 1;
        $this->seed(['id' => $id] + $data);
        return $id;
    }

    public function update(int $id, array $data): void
    {
        if (empty($data['password_hash'])) {
            unset($data['password_hash']);
        }
        $this->usuarios[$id] = array_merge($this->usuarios[$id], $data);
    }

    public function setEstado(int $id, string $estado): void
    {
        $this->usuarios[$id]['estado'] = $estado;
    }
}
