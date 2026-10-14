<?php

declare(strict_types=1);

namespace ControlVet\Clinica\Microservicio;

/**
 * Puerto de persistencia del servicio clínica (tablas historias_clinicas y
 * formulas_medicas de cv_clinica). Las historias son inmutables: no existe
 * operación de actualización ni de borrado a propósito.
 */
interface HistoriaRepository
{
    /**
     * @param array{mascota_id?:int,propietario_id?:int,veterinario_id?:int} $filtros
     * @return array<int,array<string,mixed>>
     */
    public function buscar(array $filtros, int $limite): array;

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array;

    /** @param array<string,mixed> $d */
    public function create(array $d): int;

    /** @return array<int,array<string,mixed>> */
    public function formulas(int $historiaId): array;

    /** @return array<string,mixed>|null */
    public function formula(int $id): ?array;

    /** @param array<string,mixed> $d */
    public function createFormula(int $historiaId, array $d): int;
}
