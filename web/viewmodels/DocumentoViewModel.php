<?php
/**
 * Ruta: controlvet/viewmodels/DocumentoViewModel.php
 * Listado general de documentos generados (reportes/documentos).
 */

class DocumentoViewModel
{
    public function index(): void
    {
        $usuario = Auth::user();
        Auth::requireRole(['administrador', 'recepcionista', 'veterinario', 'cliente']);

        if ($usuario['rol'] === 'cliente') {
            $documentos = $usuario['propietario_id'] ? Documento::porPropietario($usuario['propietario_id']) : [];
        } else {
            $documentos = Documento::all();
        }

        require __DIR__ . '/../views/documentos/index.php';
    }
}
