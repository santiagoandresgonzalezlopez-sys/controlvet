<?php

declare(strict_types=1);

namespace ControlVet\Auth\Microservicio;

/**
 * Puerto de persistencia del servicio de autenticación. Propiedad
 * exclusiva de este servicio: tablas `roles` y `usuarios` del esquema cv_auth.
 */
interface UsuarioRepository
{
    /** @return array<string,mixed>|null Incluye password_hash y rol_nombre. */
    public function findActiveByEmail(string $email): ?array;

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array;

    /** @return array<int,array<string,mixed>> */
    public function all(): array;

    /** @return array<int,array<string,mixed>> */
    public function veterinarios(bool $incluirInactivos): array;

    /** @return array<int,array<string,mixed>> */
    public function roles(): array;

    public function rolExists(int $rolId): bool;

    public function emailExists(string $email, ?int $excludeId = null): bool;

    /** @param array<string,mixed> $data password_hash ya calculado. */
    public function create(array $data): int;

    /** @param array<string,mixed> $data */
    public function update(int $id, array $data): void;

    public function setEstado(int $id, string $estado): void;
}
