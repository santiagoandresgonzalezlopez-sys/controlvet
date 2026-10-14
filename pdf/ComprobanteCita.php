<?php
/** Ruta: controlvet/pdf/ComprobanteCita.php */

require_once __DIR__ . '/../core/SimplePDF.php';

class ComprobanteCita extends SimplePDF
{
    public function __construct(array $cita)
    {
        $this->setDocumentTitle('Clínica Veterinaria ControlVet');
        $this->addSubtitle('Comprobante de Cita N.° ' . $cita['id']);
        $this->addSeparator();
        $this->addSpace();

        $this->addTitle('Datos del propietario');
        $this->addLine('Nombre: ' . $cita['propietario_nombre'] . ' ' . $cita['propietario_apellido']);
        $this->addSpace();

        $this->addTitle('Datos de la mascota');
        $this->addLine('Nombre: ' . $cita['mascota_nombre']);
        $this->addSpace();

        $this->addTitle('Datos de la cita');
        $this->addLine('Veterinario: Dr(a). ' . $cita['vet_nombre'] . ' ' . $cita['vet_apellido']);
        $this->addLine('Fecha: ' . $cita['fecha'] . '   Hora: ' . substr($cita['hora'], 0, 5));
        $this->addLine('Motivo: ' . $cita['motivo']);
        $this->addLine('Estado: ' . ucfirst($cita['estado']));
        if (!empty($cita['observaciones'])) {
            $this->addLine('Observaciones: ' . $cita['observaciones']);
        }
        $this->addSpace();
        $this->addSeparator();
        $this->addLine('Documento generado automáticamente por ControlVet el ' . date('Y-m-d H:i'));
    }
}
