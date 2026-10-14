<?php

declare(strict_types=1);

namespace ControlVet\Pacientes\Microservicio;

/**
 * Puerto de persistencia del servicio pacientes. Propiedad exclusiva:
 * tablas `propietarios` y `mascotas` del esquema cv_pacientes.
 */
interface PacientesRepository
{
    /** @return array<int,array<string,mixed>> */
    public function propietarios(string $buscar): array;

    /** @return array<string,mixed>|null */
    public function propietario(int $id): ?array;

    public function documentoExists(string $documento, ?int $excludeId): bool;

    /** @param array<string,mixed> $d */
    public function crearPropietario(array $d): int;

    /** @param array<string,mixed> $d */
    public function actualizarPropietario(int $id, array $d): void;

    public function desactivarPropietario(int $id): void;

    public function contarMascotasActivas(int $propietarioId): int;

    public function contarPropietarios(): int;

    /**
     * @param array<int,int> $ids
     * @return array<int,array<string,mixed>>
     */
    public function mascotas(string $buscar, ?int $propietarioId, array $ids): array;

    /** @return array<string,mixed>|null Incluye datos de contacto del propietario. */
    public function mascota(int $id): ?array;

    /** @param array<string,mixed> $d */
    public function crearMascota(array $d): int;

    /** @param array<string,mixed> $d */
    public function actualizarMascota(int $id, array $d): void;

    public function actualizarPeso(int $id, float $peso): void;

    public function contarMascotas(): int;
}
