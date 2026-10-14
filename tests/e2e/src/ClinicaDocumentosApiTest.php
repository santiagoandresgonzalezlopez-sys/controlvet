<?php

declare(strict_types=1);

namespace ControlVet\E2E;

use PHPUnit\Framework\TestCase;

final class ClinicaDocumentosApiTest extends TestCase
{
    public function testConsultaActualizaPesoYGeneraResumenYFormula(): void
    {
        $vet = Http::token('veterinario');
        $peso = round(random_int(250, 350) / 10, 1);

        $h = Http::api('POST', 'clinica', '/historias', $vet, [
            'mascota_id' => 1, 'motivo_consulta' => 'Control E2E', 'peso' => (string)$peso, 'diagnostico' => 'Paciente estable',
            'veterinario_id' => 999, // debe ignorarse: el autor sale del JWT
        ]);
        self::assertSame(201, $h['status']);
        self::assertSame(3, (int)$h['json']['veterinario_id']);
        self::assertSame(1, (int)$h['json']['propietario_id']);
        self::assertTrue($h['json']['peso_sincronizado']);

        $mascota = Http::api('GET', 'pacientes', '/mascotas/1', $vet);
        self::assertEquals($peso, (float)$mascota['json']['peso_actual'], 'El peso se sincroniza con el servicio pacientes');

        $f = Http::api('POST', 'clinica', "/historias/{$h['json']['id']}/formulas", $vet, [
            'medicamento' => 'Amoxicilina', 'dosis' => '250 mg', 'frecuencia' => 'Cada 12 horas', 'duracion' => '7 días',
        ]);
        self::assertSame(201, $f['status']);

        $resumen = Http::api('POST', 'documentos', "/documentos/resumen-consulta/{$h['json']['id']}", Http::token('cliente'), []);
        self::assertSame(200, $resumen['status'], 'El dueño puede descargar el resumen de su mascota');
        self::assertStringStartsWith('%PDF-1.4', $resumen['body']);

        $formula = Http::api('POST', 'documentos', "/documentos/formula-medica/{$f['json']['id']}", $vet, []);
        self::assertSame(200, $formula['status']);
        self::assertStringContainsString(mb_convert_encoding('Fórmula Médica', 'Windows-1252', 'UTF-8'), $formula['body']);

        $docs = array_column(Http::api('GET', 'documentos', '/documentos', Http::token('administrador'))['json'], 'nombre_archivo');
        self::assertContains("formula_medica_{$f['json']['id']}.pdf", $docs);
    }

    public function testHistoriaEsInmutable(): void
    {
        $vet = Http::token('veterinario');
        self::assertSame(405, Http::api('PUT', 'clinica', '/historias/1', $vet, ['diagnostico' => 'alterado'])['status']);
        self::assertSame(405, Http::api('PATCH', 'clinica', '/historias/1', $vet, ['diagnostico' => 'alterado'])['status']);
    }

    public function testConsultaSinDiagnosticoEsRechazada(): void
    {
        $r = Http::api('POST', 'clinica', '/historias', Http::token('veterinario'), ['mascota_id' => 1, 'motivo_consulta' => 'x']);
        self::assertSame(422, $r['status']);
        self::assertSame('El campo diagnóstico es obligatorio.', $r['json']['errors']['diagnostico']);
    }

    public function testDocumentoDeReferenciaInexistenteRetorna404(): void
    {
        self::assertSame(404, Http::api('POST', 'documentos', '/documentos/comprobante-cita/999999', Http::token('administrador'), [])['status']);
    }
}
