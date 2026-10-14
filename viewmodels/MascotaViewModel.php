<?php
/**
 * Ruta: controlvet/viewmodels/MascotaViewModel.php
 * Módulo de mascotas: cada mascota se asocia obligatoriamente a un propietario.
 */

class MascotaViewModel
{
    private array $rolesPermitidos = ['administrador', 'recepcionista'];

    public function index(string $buscar = ''): void
    {
        Auth::requireRole(array_merge($this->rolesPermitidos, ['veterinario']));
        $mascotas = Mascota::all($buscar);
        require __DIR__ . '/../views/mascotas/index.php';
    }

    public function crearForm(): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $mascota = null;
        $propietarios = Propietario::all();
        $errores = [];
        require __DIR__ . '/../views/mascotas/form.php';
    }

    public function editarForm(int $id): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $mascota = Mascota::find($id);
        if (!$mascota) { http_response_code(404); require __DIR__ . '/../views/errors/404.php'; return; }
        $propietarios = Propietario::all();
        $errores = [];
        require __DIR__ . '/../views/mascotas/form.php';
    }

    public function detalle(int $id): void
    {
        Auth::requireLogin();
        $mascota = Mascota::find($id);
        if (!$mascota) { http_response_code(404); require __DIR__ . '/../views/errors/404.php'; return; }

        // Un cliente solo puede ver información de sus propias mascotas.
        $usuario = Auth::user();
        if ($usuario['rol'] === 'cliente' && $mascota['propietario_id'] != $usuario['propietario_id']) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            return;
        }

        $historial = HistoriaClinica::porMascota($id);
        require __DIR__ . '/../views/mascotas/detalle.php';
    }

    public function guardar(array $post): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $id = !empty($post['id']) ? (int)$post['id'] : null;

        $v = new Validator();
        $v->required($post, 'nombre', 'nombre')
          ->required($post, 'especie', 'especie')
          ->required($post, 'sexo', 'sexo')
          ->required($post, 'propietario_id', 'propietario')
          ->custom(!empty($post['propietario_id']), 'propietario_id', 'No es posible registrar una mascota sin propietario.')
          ->numeric($post, 'peso_actual', 'peso')
          ->date($post, 'fecha_nacimiento', 'nacimiento');

        if (!$v->passes()) {
            $mascota = $id ? Mascota::find($id) : null;
            $propietarios = Propietario::all();
            $errores = $v->errors();
            require __DIR__ . '/../views/mascotas/form.php';
            return;
        }

        if ($id) {
            Mascota::update($id, $post);
        } else {
            Mascota::create($post);
        }

        header('Location: index.php?route=mascotas');
        exit;
    }
}
