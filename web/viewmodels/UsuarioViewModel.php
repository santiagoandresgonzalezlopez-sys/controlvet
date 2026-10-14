<?php
/**
 * Ruta: controlvet/viewmodels/UsuarioViewModel.php
 * Módulo exclusivo del administrador: CRUD de usuarios y roles.
 */

class UsuarioViewModel
{
    private array $rolesPermitidos = ['administrador'];

    public function index(): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $usuarios = Usuario::all();
        require __DIR__ . '/../views/usuarios/index.php';
    }

    public function crearForm(): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $usuario = null;
        $roles = Rol::all();
        $errores = [];
        require __DIR__ . '/../views/usuarios/form.php';
    }

    public function editarForm(int $id): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $usuario = Usuario::findById($id);
        if (!$usuario) { $this->noEncontrado(); return; }
        $roles = Rol::all();
        $errores = [];
        require __DIR__ . '/../views/usuarios/form.php';
    }

    public function guardar(array $post): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $id = !empty($post['id']) ? (int)$post['id'] : null;

        $v = new Validator();
        $v->required($post, 'nombre', 'nombre')
          ->required($post, 'apellido', 'apellido')
          ->required($post, 'email', 'correo electrónico')
          ->email($post, 'email')
          ->required($post, 'rol_id', 'rol');

        if (Usuario::emailExists($post['email'], $id)) {
            $v->custom(false, 'email', 'Ya existe un usuario registrado con este correo.');
        }
        if (!$id) {
            $v->required($post, 'password', 'contraseña')->minLength($post, 'password', 6, 'contraseña');
        }

        if (!$v->passes()) {
            $usuario = $id ? Usuario::findById($id) : null;
            $roles = Rol::all();
            $errores = $v->errors();
            require __DIR__ . '/../views/usuarios/form.php';
            return;
        }

        try {
            $id ? Usuario::update($id, $post) : Usuario::create($post);
        } catch (ApiValidationException $e) {
            $usuario = $id ? Usuario::findById($id) : null;
            $roles = Rol::all();
            $errores = $e->errors;
            require __DIR__ . '/../views/usuarios/form.php';
            return;
        }

        header('Location: index.php?route=usuarios');
        exit;
    }

    public function desactivar(int $id): void
    {
        Auth::requireRole($this->rolesPermitidos);
        try {
            Usuario::setEstado($id, 'inactivo');
        } catch (ApiConflictException $e) {
            $_SESSION['flash'] = ['tipo' => 'danger', 'mensaje' => $e->getMessage()];
        }
        header('Location: index.php?route=usuarios');
        exit;
    }

    public function activar(int $id): void
    {
        Auth::requireRole($this->rolesPermitidos);
        Usuario::setEstado($id, 'activo');
        header('Location: index.php?route=usuarios');
        exit;
    }

    private function noEncontrado(): void
    {
        http_response_code(404);
        require __DIR__ . '/../views/errors/404.php';
    }
}
