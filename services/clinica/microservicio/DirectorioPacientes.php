<?php

declare(strict_types=1);

namespace ControlVet\Clinica\Microservicio;

/**
 * Puerto hacia el servicio pacientes (dueño de las mascotas).
 */
interface DirectorioPacientes
{
    /** @return array{id:int,propietario_id:int,estado:string}|null */
    public function mascota(int $id): ?array;

    /** Retorna false si no fue posible sincronizar (se registra y se reintenta manualmente). */
    public function actualizarPeso(int $mascotaId, float $peso): bool;
}
