<?php
/**
 * Ruta: controlvet/viewmodels/HistoriaViewModel.php
 * Historia clínica: registro inmutable + generación de resumen y fórmula.
 */

class HistoriaViewModel
{
    public function crearForm(int $mascotaId, ?int $citaId = null): void
    {
        Auth::requireRole(['veterinario']);
        $mascota = Mascota::find($mascotaId);
        if (!$mascota) { http_response_code(404); require __DIR__ . '/../views/errors/404.php'; return; }
        $errores = [];
        require __DIR__ . '/../views/historias/form.php';
    }

    public function guardar(array $post): void
    {
        Auth::requireRole(['veterinario']);
        $usuario = Auth::user();

        $v = new Validator();
        $v->required($post, 'mascota_id', 'paciente')
          ->required($post, 'motivo_consulta', 'motivo de consulta')
          ->required($post, 'diagnostico', 'diagnóstico')
          ->numeric($post, 'peso', 'peso');

        if (!$v->passes()) {
            $mascota = Mascota::find((int)$post['mascota_id']);
            $errores = $v->errors();
            require __DIR__ . '/../views/historias/form.php';
            return;
        }

        // El servicio clínica toma el veterinario autor del JWT, no del formulario.
        try {
            $id = HistoriaClinica::create($post);
        } catch (ApiValidationException $e) {
            $mascota = Mascota::find((int)$post['mascota_id']);
            $errores = $e->errors;
            require __DIR__ . '/../views/historias/form.php';
            return;
        }

        header('Location: index.php?route=historias.detalle&id=' . $id);
        exit;
    }

    public function detalle(int $id): void
    {
        Auth::requireLogin();
        $historia = HistoriaClinica::find($id);
        if (!$historia) { http_response_code(404); require __DIR__ . '/../views/errors/404.php'; return; }

        $usuario = Auth::user();
        if ($usuario['rol'] === 'cliente' && $historia['propietario_id'] != $usuario['propietario_id']) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            return;
        }

        $formulas = HistoriaClinica::formulasPorHistoria($id);
        require __DIR__ . '/../views/historias/detalle.php';
    }

    public function crearFormulaForm(int $historiaId): void
    {
        Auth::requireRole(['veterinario']);
        $historia = HistoriaClinica::find($historiaId);
        if (!$historia) { http_response_code(404); require __DIR__ . '/../views/errors/404.php'; return; }
        $errores = [];
        require __DIR__ . '/../views/historias/formula_form.php';
    }

    public function guardarFormula(array $post): void
    {
        Auth::requireRole(['veterinario']);
        $v = new Validator();
        $v->required($post, 'medicamento', 'medicamento')
          ->required($post, 'dosis', 'dosis')
          ->required($post, 'frecuencia', 'frecuencia')
          ->required($post, 'duracion', 'duración');

        $historiaId = (int)$post['historia_id'];
        if (!$v->passes()) {
            $historia = HistoriaClinica::find($historiaId);
            $errores = $v->errors();
            require __DIR__ . '/../views/historias/formula_form.php';
            return;
        }

        try {
            HistoriaClinica::crearFormula($historiaId, $post);
        } catch (ApiValidationException $e) {
            $historia = HistoriaClinica::find($historiaId);
            $errores = $e->errors;
            require __DIR__ . '/../views/historias/formula_form.php';
            return;
        }
        header('Location: index.php?route=historias.detalle&id=' . $historiaId);
        exit;
    }

    public function resumenPdf(int $id): void
    {
        Auth::requireLogin();
        $historia = HistoriaClinica::find($id);
        if (!$historia) { http_response_code(404); require __DIR__ . '/../views/errors/404.php'; return; }

        $usuario = Auth::user();
        if ($usuario['rol'] === 'cliente' && $historia['propietario_id'] != $usuario['propietario_id']) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            return;
        }

        $pdf = Documento::generar('resumen_consulta', (int)$historia['id']);
        if ($pdf === null) { http_response_code(404); require __DIR__ . '/../views/errors/404.php'; return; }
        Documento::enviarPdf($pdf, 'resumen_consulta_' . $historia['id'] . '.pdf');
        exit;
    }

    public function formulaPdf(int $id): void
    {
        Auth::requireLogin();
        $formula = HistoriaClinica::findFormula($id);
        if (!$formula) { http_response_code(404); require __DIR__ . '/../views/errors/404.php'; return; }

        $historia = HistoriaClinica::find($formula['historia_id']);
        $usuario = Auth::user();
        if ($usuario['rol'] === 'cliente' && $historia['propietario_id'] != $usuario['propietario_id']) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            return;
        }

        $pdf = Documento::generar('formula_medica', (int)$formula['id']);
        if ($pdf === null) { http_response_code(404); require __DIR__ . '/../views/errors/404.php'; return; }
        Documento::enviarPdf($pdf, 'formula_medica_' . $formula['id'] . '.pdf');
        exit;
    }
}
