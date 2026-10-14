<?php

declare(strict_types=1);

namespace ControlVet\Citas\Microservicio;

/**
 * Puerto hacia los servicios dueños de los datos que una cita referencia:
 * mascotas (servicio pacientes) y veterinarios (servicio auth).
 * El servicio citas NUNCA accede a esas bases de datos directamente.
 */
interface DirectorioClinico
{
    /** @return array{id:int,propietario_id:int,estado:string}|null */
    public function mascota(int $id): ?array;

    /** @return array{id:int,estado:string}|null */
    public function veterinario(int $id): ?array;
}
