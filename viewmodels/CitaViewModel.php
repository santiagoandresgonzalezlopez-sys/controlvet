<?php
/**
 * Ruta: controlvet/viewmodels/CitaViewModel.php
 * Módulo de agenda/citas.
 */

class CitaViewModel
{
    public function index(array $filtros = []): void
    {
        $usuario = Auth::user();
        Auth::requireRole(['administrador', 'recepcionista', 'veterinario', 'cliente']);

        if ($usuario['rol'] === 'veterinario') {
            $filtros['veterinario_id'] = $usuario['id'];
        } elseif ($usuario['rol'] === 'cliente') {
            $filtros['propietario_id'] = $usuario['propietario_id'];
        }

        $citas = Cita::all($filtros);
        $veterinarios = Cita::veterinarios();
        require __DIR__ . '/../views/citas/index.php';
    }

    public function crearForm(): void
    {
        Auth::requireRole(['administrador', 'recepcionista']);
        $cita = null;
        $mascotas = Mascota::all();
        $veterinarios = Cita::veterinarios();
        $errores = [];
        require __DIR__ . '/../views/citas/form.php';
    }

    public function editarForm(int $id): void
    {
        Auth::requireRole(['administrador', 'recepcionista']);
        $cita = Cita::find($id);
        if (!$cita) { http_response_code(404); require __DIR__ . '/../views/errors/404.php'; return; }
        $mascotas = Mascota::all();
        $veterinarios = Cita::veterinarios();
        $errores = [];
        require __DIR__ . '/../views/citas/form.php';
    }

    public function guardar(array $post): void
    {
        Auth::requireRole(['administrador', 'recepcionista']);
        $id = !empty($post['id']) ? (int)$post['id'] : null;

        $v = new Validator();
        $v->required($post, 'mascota_id', 'paciente')
          ->custom(!empty($post['mascota_id']), 'mascota_id', 'No es posible registrar una cita sin paciente.')
          ->required($post, 'veterinario_id', 'veterinario')
          ->custom(!empty($post['veterinario_id']), 'veterinario_id', 'No es posible registrar una cita sin veterinario.')
          ->required($post, 'fecha', 'fecha')
          ->date($post, 'fecha', 'la cita')
          ->required($post, 'hora', 'hora')
          ->required($post, 'motivo', 'motivo');

        if (!$v->passes()) {
            $cita = $id ? Cita::find($id) : null;
            $mascotas = Mascota::all();
            $veterinarios = Cita::veterinarios();
            $errores = $v->errors();
            require __DIR__ . '/../views/citas/form.php';
            return;
        }

        if ($id) {
            Cita::update($id, $post);
        } else {
            $post['creado_por'] = Auth::user()['id'];
            Cita::create($post);
        }

        header('Location: index.php?route=citas');
        exit;
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        Auth::requireRole(['administrador', 'recepcionista', 'veterinario']);
        $permitidos = ['programada', 'confirmada', 'atendida', 'cancelada'];
        if (in_array($estado, $permitidos, true)) {
            Cita::cambiarEstado($id, $estado);
        }
        header('Location: index.php?route=citas');
        exit;
    }

    public function comprobantePdf(int $id): void
    {
        Auth::requireLogin();
        $cita = Cita::find($id);
        if (!$cita) { http_response_code(404); require __DIR__ . '/../views/errors/404.php'; return; }

        $usuario = Auth::user();
        if ($usuario['rol'] === 'cliente' && $cita['propietario_id'] != $usuario['propietario_id']) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            return;
        }

        require_once __DIR__ . '/../pdf/ComprobanteCita.php';
        $pdf = new ComprobanteCita($cita);
        $filename = 'comprobante_cita_' . $cita['id'] . '.pdf';
        Documento::registrar('comprobante_cita', $cita['id'], $filename, $usuario['id']);
        $pdf->output($filename);
        exit;
    }
}
