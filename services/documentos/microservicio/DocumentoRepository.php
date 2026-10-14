<?php

declare(strict_types=1);

namespace ControlVet\Documentos\Microservicio;

/**
 * Puerto de persistencia del registro de documentos (tabla documentos de cv_documentos).
 */
interface DocumentoRepository
{
    public function registrar(string $tipo, int $referenciaId, string $nombreArchivo, int $generadoPor, int $propietarioId): int;

    /** @return array<int,array<string,mixed>> */
    public function todos(): array;

    /** @return array<int,array<string,mixed>> */
    public function porPropietario(int $propietarioId): array;
}
