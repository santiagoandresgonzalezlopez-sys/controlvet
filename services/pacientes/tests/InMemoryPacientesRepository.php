<?php

declare(strict_types=1);

namespace ControlVet\Pacientes\Tests;

use ControlVet\Pacientes\Microservicio\PacientesRepository;

final class InMemoryPacientesRepository implements PacientesRepository
{
    /** @var array<int,array<string,mixed>> */
    public array $propietarios = [];
    /** @var array<int,array<string,mixed>> */
    public array $mascotas = [];

    public function propietarios(string $buscar): array
    {
        return array_values(array_filter($this->propietarios, fn($p) => $p['estado'] === 'activo'
            && ($buscar === '' || str_contains($p['nombre'] . $p['apellido'] . $p['documento'], $buscar))));
    }

    public function propietario(int $id): ?array
    {
        return $this->propietarios[$id] ?? null;
    }

    public function documentoExists(string $documento, ?int $excludeId): bool
    {
        foreach ($this->propietarios as $p) {
            if ($p['documento'] === $documento && $p['id'] !== $excludeId) {
                return true;
            }
        }
        return false;
    }

    public function crearPropietario(array $d): int
    {
        $id = count($this->propietarios) + 1;
        $this->propietarios[$id] = ['id' => $id, 'estado' => 'activo'] + $d;
        return $id;
    }

    public function actualizarPropietario(int $id, array $d): void
    {
        $this->propietarios[$id] = array_merge($this->propietarios[$id], $d);
    }

    public function desactivarPropietario(int $id): void
    {
        $this->propietarios[$id]['estado'] = 'inactivo';
    }

    public function contarMascotasActivas(int $propietarioId): int
    {
        return count(array_filter($this->mascotas, fn($m) => $m['propietario_id'] === $propietarioId && $m['estado'] === 'activo'));
    }

    public function contarPropietarios(): int
    {
        return count($this->propietarios(''));
    }

    public function mascotas(string $buscar, ?int $propietarioId, array $ids): array
    {
        return array_values(array_filter($this->mascotas, fn($m) =>
            ($ids ? in_array($m['id'], $ids, true) : $m['estado'] === 'activo')
            && ($propietarioId === null || $m['propietario_id'] === $propietarioId)
            && ($buscar === '' || str_contains($m['nombre'], $buscar))));
    }

    public function mascota(int $id): ?array
    {
        return $this->mascotas[$id] ?? null;
    }

    public function crearMascota(array $d): int
    {
        $id = count($this->mascotas) + 1;
        $this->mascotas[$id] = ['id' => $id, 'estado' => 'activo', 'propietario_id' => (int)$d['propietario_id']] + $d;
        return $id;
    }

    public function actualizarMascota(int $id, array $d): void
    {
        $this->mascotas[$id] = array_merge($this->mascotas[$id], $d, ['propietario_id' => (int)$d['propietario_id']]);
    }

    public function actualizarPeso(int $id, float $peso): void
    {
        $this->mascotas[$id]['peso_actual'] = $peso;
    }

    public function contarMascotas(): int
    {
        return count($this->mascotas('', null, []));
    }
}
