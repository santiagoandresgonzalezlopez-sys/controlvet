<?php

declare(strict_types=1);

namespace ControlVet\Documentos\Microservicio;

use ControlVet\Kernel\Http\ServiceClient;

final class HttpFuentesDocumentales implements FuentesDocumentales
{
    public function __construct(
        private readonly ServiceClient $citas,
        private readonly ServiceClient $clinica,
        private readonly ServiceClient $pacientes,
        private readonly ServiceClient $auth,
    ) {
    }

    public function cita(int $id): ?array
    {
        return $this->citas->get('/citas/' . $id);
    }

    public function historia(int $id): ?array
    {
        return $this->clinica->get('/historias/' . $id);
    }

    public function formula(int $id): ?array
    {
        return $this->clinica->get('/formulas/' . $id);
    }

    public function mascota(int $id): ?array
    {
        return $this->pacientes->get('/mascotas/' . $id);
    }

    public function veterinario(int $id): ?array
    {
        return $this->auth->get('/veterinarios/' . $id);
    }
}
