<?php

declare(strict_types=1);

namespace ControlVet\Citas\Microservicio;

use ControlVet\Kernel\Http\ServiceClient;

/**
 * Adaptador HTTP del directorio clínico. Reenvía el JWT del usuario, de modo
 * que pacientes y auth aplican sus propias reglas de autorización.
 */
final class HttpDirectorioClinico implements DirectorioClinico
{
    public function __construct(
        private readonly ServiceClient $pacientes,
        private readonly ServiceClient $auth,
    ) {
    }

    public function mascota(int $id): ?array
    {
        $m = $this->pacientes->get('/mascotas/' . $id);
        return $m ? ['id' => (int)$m['id'], 'propietario_id' => (int)$m['propietario_id'], 'estado' => (string)$m['estado']] : null;
    }

    public function veterinario(int $id): ?array
    {
        $v = $this->auth->get('/veterinarios/' . $id);
        return $v ? ['id' => (int)$v['id'], 'estado' => (string)$v['estado']] : null;
    }
}
