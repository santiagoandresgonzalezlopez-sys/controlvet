<?php
/**
 * Ruta: controlvet/viewmodels/PropietarioViewModel.php
 * Módulo de propietarios (recepcionista y administrador).
 */

class PropietarioViewModel
{
    private array $rolesPermitidos = ['administrador', 'recepcionista'];

    public function index(string $buscar = ''): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $propietarios = Propietario::all($buscar);
        require __DIR__ . '/../views/propietarios/index.php';
    }

    public function crearForm(): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $propietario = null;
        $errores = [];
        require __DIR__ . '/../views/propietarios/form.php';
    }

    public function editarForm(int $id): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $propietario = Propietario::find($id);
        if (!$propietario) { http_response_code(404); require __DIR__ . '/../views/errors/404.php'; return; }
        $errores = [];
        require __DIR__ . '/../views/propietarios/form.php';
    }

    public function detalle(int $id): void
    {
        Auth::requireRole(array_merge($this->rolesPermitidos, ['veterinario']));
        $propietario = Propietario::find($id);
        if (!$propietario) { http_response_code(404); require __DIR__ . '/../views/errors/404.php'; return; }
        $mascotas = Propietario::mascotas($id);
        require __DIR__ . '/../views/propietarios/detalle.php';
    }

    public function guardar(array $post): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $id = !empty($post['id']) ? (int)$post['id'] : null;

        $v = new Validator();
        $v->required($post, 'nombre', 'nombre')
          ->required($post, 'apellido', 'apellido')
          ->required($post, 'documento', 'documento')
          ->required($post, 'telefono', 'teléfono')
          ->email($post, 'email');

        if (Propietario::documentoExists($post['documento'], $id)) {
            $v->custom(false, 'documento', 'Ya existe un propietario registrado con este documento.');
        }

        if (!$v->passes()) {
            $propietario = $id ? Propietario::find($id) : null;
            $errores = $v->errors();
            require __DIR__ . '/../views/propietarios/form.php';
            return;
        }

        if ($id) {
            Propietario::update($id, $post);
        } else {
            Propietario::create($post);
        }

        header('Location: index.php?route=propietarios');
        exit;
    }

    public function desactivar(int $id): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $ok = Propietario::desactivar($id);
        $_SESSION['flash'] = $ok
            ? ['tipo' => 'success', 'mensaje' => 'Propietario desactivado correctamente.']
            : ['tipo' => 'danger', 'mensaje' => 'No es posible desactivar: el propietario tiene mascotas activas asociadas.'];
        header('Location: index.php?route=propietarios');
        exit;
    }
}
