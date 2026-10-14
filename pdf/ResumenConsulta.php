<?php
/** Ruta: controlvet/pdf/ResumenConsulta.php */

require_once __DIR__ . '/../core/SimplePDF.php';

class ResumenConsulta extends SimplePDF
{
    public function __construct(array $h)
    {
        $this->setDocumentTitle('Clínica Veterinaria ControlVet');
        $this->addSubtitle('Resumen de Consulta N.° ' . $h['id']);
        $this->addSeparator();
        $this->addSpace();

        $this->addTitle('Paciente');
        $this->addLine('Nombre: ' . $h['mascota_nombre'] . ' (' . $h['especie'] . ')');
        $this->addLine('Propietario: ' . $h['propietario_nombre'] . ' ' . $h['propietario_apellido']);
        $this->addSpace();

        $this->addTitle('Consulta');
        $this->addLine('Veterinario: Dr(a). ' . $h['vet_nombre'] . ' ' . $h['vet_apellido']);
        $this->addLine('Fecha: ' . $h['fecha']);
        $this->addLine('Motivo: ' . $h['motivo_consulta']);
        $this->addLine('Peso: ' . ($h['peso'] ?? 'N/A') . ' kg');
        $this->addLine('Síntomas: ' . ($h['sintomas'] ?: 'N/A'));
        $this->addLine('Diagnóstico: ' . $h['diagnostico']);
        $this->addLine('Tratamiento: ' . ($h['tratamiento'] ?: 'N/A'));
        if (!empty($h['observaciones'])) {
            $this->addLine('Observaciones: ' . $h['observaciones']);
        }
        $this->addSpace();
        $this->addSeparator();
        $this->addLine('Documento generado automáticamente por ControlVet el ' . date('Y-m-d H:i'));
    }
}
