<?php

declare(strict_types=1);

namespace ControlVet\Documentos\Microservicio;

/**
 * Puerto hacia los servicios dueños de la información que se imprime.
 * El contenido de un documento SIEMPRE se obtiene de la fuente de verdad
 * (con el JWT del solicitante), nunca del cuerpo de la petición: así un
 * cliente no puede fabricar un comprobante ni leer datos ajenos.
 */
interface FuentesDocumentales
{
    /** @return array<string,mixed>|null */
    public function cita(int $id): ?array;

    /** @return array<string,mixed>|null */
    public function historia(int $id): ?array;

    /** @return array<string,mixed>|null */
    public function formula(int $id): ?array;

    /** @return array<string,mixed>|null Incluye propietario_nombre/apellido y especie. */
    public function mascota(int $id): ?array;

    /** @return array<string,mixed>|null */
    public function veterinario(int $id): ?array;
}
