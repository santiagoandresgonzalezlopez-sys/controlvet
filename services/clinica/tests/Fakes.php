<?php

declare(strict_types=1);

namespace ControlVet\Clinica\Tests;

use ControlVet\Clinica\Microservicio\DirectorioPacientes;
use ControlVet\Clinica\Microservicio\HistoriaRepository;

final class InMemoryHistoriaRepository implements HistoriaRepository
{
    /** @var array<int,array<string,mixed>> */
    public array $historias = [];
    /** @var array<int,array<string,mixed>> */
    public array $formulas = [];

    public function buscar(array $filtros, int $limite): array
    {
        $r = array_filter($this->historias, function ($h) use ($filtros) {
            foreach ($filtros as $k => $v) {
                if ($h[$k] !== $v) {
                    return false;
                }
            }
            return true;
        });
        return array_slice(array_reverse(array_values($r)), 0, $limite);
    }

    public function find(int $id): ?array
    {
        return $this->historias[$id] ?? null;
    }

    public function create(array $d): int
    {
        $id = count($this->historias) + 1;
        $this->historias[$id] = ['id' => $id, 'fecha' => date('Y-m-d H:i:s')] + $d;
        return $id;
    }

    public function formulas(int $historiaId): array
    {
        return array_values(array_filter($this->formulas, fn($f) => $f['historia_id'] === $historiaId));
    }

    public function formula(int $id): ?array
    {
        $f = $this->formulas[$id] ?? null;
        if ($f === null) {
            return null;
        }
        $h = $this->historias[$f['historia_id']];
        return $f + ['mascota_id' => $h['mascota_id'], 'veterinario_id' => $h['veterinario_id'], 'propietario_id' => $h['propietario_id']];
    }

    public function createFormula(int $historiaId, array $d): int
    {
        $id = count($this->formulas) + 1;
        $this->formulas[$id] = ['id' => $id, 'historia_id' => $historiaId] + $d;
        return $id;
    }
}

final class FakeDirectorioPacientes implements DirectorioPacientes
{
    /** @var array<int,float> */
    public array $pesos = [];
    public bool $disponible = true;

    public function mascota(int $id): ?array
    {
        return match ($id) {
            1 => ['id' => 1, 'propietario_id' => 1, 'estado' => 'activo'],
            4 => ['id' => 4, 'propietario_id' => 3, 'estado' => 'activo'],
            8 => ['id' => 8, 'propietario_id' => 3, 'estado' => 'inactivo'],
            default => null,
        };
    }

    public function actualizarPeso(int $mascotaId, float $peso): bool
    {
        if (!$this->disponible) {
            return false;
        }
        $this->pesos[$mascotaId] = $peso;
        return true;
    }
}
