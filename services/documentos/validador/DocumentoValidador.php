<?php

declare(strict_types=1);

namespace ControlVet\Documentos\Validador;

use ControlVet\Kernel\Http\HttpException;

final class DocumentoValidador
{
    /** El enrutador solo acepta dígitos; aquí se exige además un rango válido. */
    public static function referencia(int $id): void
    {
        if ($id < 1 || $id > 2_147_483_647) {
            throw HttpException::unprocessable(['referencia_id' => 'La referencia del documento no es válida.']);
        }
    }
}
