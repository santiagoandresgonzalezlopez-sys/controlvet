<?php

declare(strict_types=1);

namespace ControlVet\E2E;

use PHPUnit\Framework\TestCase;

/**
 * El cliente de demostración (Carlos Ramírez, propietario 1) solo debe ver
 * información de sus mascotas (Rocky y Michi), aunque manipule los filtros.
 */
final class AislamientoDatosTest extends TestCase
{
    private const PROPIETARIO_CLIENTE = 1;

    public function testClienteSoloRecibeSusMascotasAunqueFiltrePorOtroPropietario(): void
    {
        $r = Http::api('GET', 'pacientes', '/mascotas?propietario_id=2', Http::token('cliente'));

        self::assertSame(200, $r['status']);
        self::assertNotEmpty($r['json']);
        foreach ($r['json'] as $m) {
            self::assertSame(self::PROPIETARIO_CLIENTE, (int)$m['propietario_id']);
        }
    }

    public function testClienteNoAccedeAMascotaAjenaPorId(): void
    {
        self::assertSame(403, Http::api('GET', 'pacientes', '/mascotas/3', Http::token('cliente'))['status']);
    }

    public function testClienteSoloVeSusCitas(): void
    {
        $r = Http::api('GET', 'citas', '/citas?propietario_id=2', Http::token('cliente'));

        self::assertSame(200, $r['status']);
        foreach ($r['json'] as $c) {
            self::assertSame(self::PROPIETARIO_CLIENTE, (int)$c['propietario_id']);
        }
        self::assertSame(403, Http::api('GET', 'citas', '/citas/2', Http::token('cliente'))['status']);
    }

    public function testClienteSoloVeSuHistorialClinico(): void
    {
        $r = Http::api('GET', 'clinica', '/historias?propietario_id=3', Http::token('cliente'));

        self::assertSame(200, $r['status']);
        foreach ($r['json'] as $h) {
            self::assertSame(self::PROPIETARIO_CLIENTE, (int)$h['propietario_id']);
        }
        self::assertSame(403, Http::api('GET', 'clinica', '/historias/1', Http::token('cliente'))['status']);
        self::assertSame(403, Http::api('GET', 'clinica', '/formulas/1', Http::token('cliente'))['status']);
    }

    public function testClienteNoGeneraDocumentosDeOtrosPropietarios(): void
    {
        $r = Http::api('POST', 'documentos', '/documentos/comprobante-cita/2', Http::token('cliente'), []);
        self::assertSame(403, $r['status']);
    }

    public function testClienteGeneraSuComprobanteYSoloListaSusDocumentos(): void
    {
        $pdf = Http::api('POST', 'documentos', '/documentos/comprobante-cita/1', Http::token('cliente'), []);
        self::assertSame(200, $pdf['status']);
        self::assertStringStartsWith('%PDF-1.4', $pdf['body']);

        $docs = Http::api('GET', 'documentos', '/documentos', Http::token('cliente'));
        self::assertNotEmpty($docs['json']);
        foreach ($docs['json'] as $d) {
            self::assertSame(self::PROPIETARIO_CLIENTE, (int)$d['propietario_id']);
        }
    }
}
