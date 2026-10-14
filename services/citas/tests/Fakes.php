<?php

declare(strict_types=1);

namespace ControlVet\Citas\Tests;

use ControlVet\Citas\Microservicio\CitaRepository;
use ControlVet\Citas\Microservicio\DirectorioClinico;

final class InMemoryCitaRepository implements CitaRepository
{
    /** @var array<int,array<string,mixed>> */
    public array $citas = [];

    public function buscar(array $filtros): array
    {
        return array_values(array_filter($this->citas, function ($c) use ($filtros) {
            foreach ($filtros as $k => $v) {
                if ($v !== '' && (string)$c[$k] !== (string)$v) {
                    return false;
                }
            }
            return true;
        }));
    }

    public function proximas(int $limite, ?int $veterinarioId, ?int $propietarioId): array
    {
        $r = array_filter($this->citas, fn($c) => $c['fecha'] >= date('Y-m-d') && $c['estado'] !== 'cancelada'
            && (!$veterinarioId || $c['veterinario_id'] === $veterinarioId)
            && (!$propietarioId || $c['propietario_id'] === $propietarioId));
        return array_slice(array_values($r), 0, $limite);
    }

    public function find(int $id): ?array
    {
        return $this->citas[$id] ?? null;
    }

    public function horarioOcupado(int $veterinarioId, string $fecha, string $hora, ?int $excluirId): bool
    {
        foreach ($this->citas as $c) {
            if ($c['veterinario_id'] === $veterinarioId && $c['fecha'] === $fecha && $c['hora'] === $hora
                && $c['estado'] !== 'cancelada' && $c['id'] !== $excluirId) {
                return true;
            }
        }
        return false;
    }

    public function create(array $d): int
    {
        $id = count($this->citas) + 1;
        $this->citas[$id] = ['id' => $id, 'estado' => 'programada'] + $d;
        return $id;
    }

    public function update(int $id, array $d): void
    {
        $this->citas[$id] = array_merge($this->citas[$id], $d);
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $this->citas[$id]['estado'] = $estado;
    }
}

final class FakeDirectorioClinico implements DirectorioClinico
{
    /** @var array<int,array{id:int,propietario_id:int,estado:string}> */
    public array $mascotas = [
        1 => ['id' => 1, 'propietario_id' => 1, 'estado' => 'activo'],
        3 => ['id' => 3, 'propietario_id' => 2, 'estado' => 'activo'],
        9 => ['id' => 9, 'propietario_id' => 2, 'estado' => 'inactivo'],
    ];
    /** @var array<int,array{id:int,estado:string}> */
    public array $veterinarios = [
        3 => ['id' => 3, 'estado' => 'activo'],
        5 => ['id' => 5, 'estado' => 'inactivo'],
    ];

    public function mascota(int $id): ?array
    {
        return $this->mascotas[$id] ?? null;
    }

    public function veterinario(int $id): ?array
    {
        return $this->veterinarios[$id] ?? null;
    }
}
