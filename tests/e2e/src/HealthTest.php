<?php

declare(strict_types=1);

namespace ControlVet\E2E;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Smoke test: se ejecuta también después de cada despliegue.
 */
final class HealthTest extends TestCase
{
    public static function servicios(): array
    {
        return array_map(fn($s) => [$s], ['auth', 'pacientes', 'citas', 'clinica', 'inventario', 'documentos']);
    }

    #[DataProvider('servicios')]
    public function testServicioSaludableConBaseDeDatos(string $servicio): void
    {
        $r = Http::api('GET', $servicio, '/health');

        self::assertSame(200, $r['status'], "$servicio /health");
        self::assertSame('ok', $r['json']['status']);
        self::assertSame($servicio, $r['json']['service']);
        self::assertSame('ok', $r['json']['checks']['database']);
        if ($esperada = getenv('EXPECTED_VERSION')) {
            self::assertSame($esperada, $r['json']['version'], 'Versión desplegada');
        }
    }

    public function testFrontendReportaTodasLasDependenciasDisponibles(): void
    {
        $r = Http::request('GET', Http::url('web') . '/health.php');

        self::assertSame(200, $r['status']);
        self::assertSame('ok', $r['json']['status']);
        self::assertCount(6, array_filter($r['json']['dependencies'], fn($s) => $s === 'ok'));
    }

    public function testPaginaDeLoginDisponible(): void
    {
        $r = Http::request('GET', Http::url('web') . '/index.php?route=login');

        self::assertSame(200, $r['status']);
        self::assertStringContainsString('ControlVet', $r['body']);
    }
}
