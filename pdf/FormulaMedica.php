<?php
/** Ruta: controlvet/pdf/FormulaMedica.php */

require_once __DIR__ . '/../core/SimplePDF.php';

class FormulaMedica extends SimplePDF
{
    public function __construct(array $formula, array $historia)
    {
        $this->setDocumentTitle('Clínica Veterinaria ControlVet');
        $this->addSubtitle('Fórmula Médica N.° ' . $formula['id']);
        $this->addSeparator();
        $this->addSpace();

        $this->addTitle('Datos generales');
        $this->addLine('Veterinario: Dr(a). ' . $historia['vet_nombre'] . ' ' . $historia['vet_apellido']);
        $this->addLine('Propietario: ' . $historia['propietario_nombre'] . ' ' . $historia['propietario_apellido']);
        $this->addLine('Mascota: ' . $historia['mascota_nombre']);
        $this->addLine('Fecha: ' . date('Y-m-d', strtotime($formula['fecha_creacion'])));
        $this->addSpace();

        $this->addTitle('Prescripción');
        $this->addLine('Medicamento: ' . $formula['medicamento']);
        $this->addLine('Dosis: ' . $formula['dosis']);
        $this->addLine('Frecuencia: ' . $formula['frecuencia']);
        $this->addLine('Duración: ' . $formula['duracion']);
        if (!empty($formula['indicaciones'])) {
            $this->addLine('Indicaciones: ' . $formula['indicaciones']);
        }
        $this->addSpace();
        $this->addSeparator();
        $this->addLine('Documento generado automáticamente por ControlVet el ' . date('Y-m-d H:i'));
    }
}
