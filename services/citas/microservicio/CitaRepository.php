<?php

declare(strict_types=1);

namespace ControlVet\Citas\Microservicio;

/**
 * Puerto de persistencia del servicio citas (tabla `citas` de cv_citas).
 */
interface CitaRepository
{
    /**
     * @param array{fecha?:string,veterinario_id?:int,estado?:string,propietario_id?:int} $filtros
     * @return array<int,array<string,mixed>>
     */
    public function buscar(array $filtros): array;

    /** @return array<int,array<string,mixed>> */
    public function proximas(int $limite, ?int $veterinarioId, ?int $propietarioId): array;

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array;

    public function horarioOcupado(int $veterinarioId, string $fecha, string $hora, ?int $excluirId): bool;

    /** @param array<string,mixed> $d */
    public function create(array $d): int;

    /** @param array<string,mixed> $d */
    public function update(int $id, array $d): void;

    public function cambiarEstado(int $id, string $estado): void;
}
