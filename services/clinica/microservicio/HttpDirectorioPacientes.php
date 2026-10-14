<?php

declare(strict_types=1);

namespace ControlVet\Clinica\Microservicio;

use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Http\ServiceClient;
use ControlVet\Kernel\Log\Logger;

final class HttpDirectorioPacientes implements DirectorioPacientes
{
    public function __construct(
        private readonly ServiceClient $pacientes,
        private readonly Logger $logger,
    ) {
    }

    public function mascota(int $id): ?array
    {
        $m = $this->pacientes->get('/mascotas/' . $id);
        return $m ? ['id' => (int)$m['id'], 'propietario_id' => (int)$m['propietario_id'], 'estado' => (string)$m['estado']] : null;
    }

    public function actualizarPeso(int $mascotaId, float $peso): bool
    {
        try {
            return $this->pacientes->patch('/mascotas/' . $mascotaId . '/peso', ['peso' => $peso]) !== null;
        } catch (HttpException $e) {
            $this->logger->warning('No se sincronizó el peso con el servicio pacientes', [
                'mascota_id' => $mascotaId, 'peso' => $peso, 'status' => $e->status(),
            ]);
            return false;
        }
    }
}
