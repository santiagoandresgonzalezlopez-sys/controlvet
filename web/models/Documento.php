<?php
/** Ruta: controlvet/web/models/Documento.php — Cliente del microservicio documentos */

class Documento
{
    /** Rutas del servicio documentos por tipo de documento. */
    private const RUTAS = [
        'comprobante_cita' => 'comprobante-cita',
        'resumen_consulta' => 'resumen-consulta',
        'formula_medica'   => 'formula-medica',
    ];

    /**
     * Solicita al servicio documentos generar y registrar un PDF. El
     * servicio obtiene el contenido de las fuentes de verdad con el JWT del
     * usuario, por lo que el aislamiento de datos se valida allí.
     */
    public static function generar(string $tipo, int $referenciaId): ?string
    {
        return Api::postBinary('documentos', '/documentos/' . self::RUTAS[$tipo] . '/' . $referenciaId);
    }

    /** El servicio limita el resultado al propietario cuando el usuario es cliente. */
    public static function porPropietario(int $propietarioId): array
    {
        return Api::get('documentos', '/documentos') ?? [];
    }

    public static function all(): array
    {
        return Api::get('documentos', '/documentos') ?? [];
    }

    public static function enviarPdf(string $pdf, string $filename): void
    {
        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($pdf));
        header('Content-Disposition: inline; filename="' . $filename . '"');
        echo $pdf;
    }
}
