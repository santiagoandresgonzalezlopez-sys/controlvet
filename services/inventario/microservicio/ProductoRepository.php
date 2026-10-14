<?php

declare(strict_types=1);

namespace ControlVet\Inventario\Microservicio;

/**
 * Puerto de persistencia del servicio inventario (tabla productos de cv_inventario).
 */
interface ProductoRepository
{
    /** @return array<int,array<string,mixed>> */
    public function activos(string $buscar): array;

    /** @return array<int,array<string,mixed>> */
    public function stockBajo(): array;

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array;

    /** @param array<string,mixed> $d */
    public function create(array $d): int;

    /** @param array<string,mixed> $d */
    public function update(int $id, array $d): void;

    public function actualizarCantidad(int $id, int $cantidad): void;

    public function desactivar(int $id): void;

    public function contarActivos(): int;
}
