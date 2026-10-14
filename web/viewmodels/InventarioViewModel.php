<?php
/**
 * Ruta: controlvet/viewmodels/InventarioViewModel.php
 * Módulo de inventario, exclusivo del administrador.
 */

class InventarioViewModel
{
    private array $rolesPermitidos = ['administrador'];

    public function index(string $buscar = ''): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $productos = Producto::all($buscar);
        require __DIR__ . '/../views/inventario/index.php';
    }

    public function crearForm(): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $producto = null;
        $errores = [];
        require __DIR__ . '/../views/inventario/form.php';
    }

    public function editarForm(int $id): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $producto = Producto::find($id);
        if (!$producto) { http_response_code(404); require __DIR__ . '/../views/errors/404.php'; return; }
        $errores = [];
        require __DIR__ . '/../views/inventario/form.php';
    }

    public function guardar(array $post): void
    {
        Auth::requireRole($this->rolesPermitidos);
        $id = !empty($post['id']) ? (int)$post['id'] : null;

        $v = new Validator();
        $v->required($post, 'nombre', 'nombre')
          ->required($post, 'categoria', 'categoría')
          ->required($post, 'stock_minimo', 'stock mínimo')
          ->numeric($post, 'stock_minimo', 'stock mínimo')
          ->numeric($post, 'precio_referencia', 'precio de referencia');

        if (!$id) {
            $v->required($post, 'cantidad_disponible', 'cantidad disponible')
              ->numeric($post, 'cantidad_disponible', 'cantidad disponible');
        }

        if (!$v->passes()) {
            $producto = $id ? Producto::find($id) : null;
            $errores = $v->errors();
            require __DIR__ . '/../views/inventario/form.php';
            return;
        }

        try {
            $id ? Producto::update($id, $post) : Producto::create($post);
        } catch (ApiValidationException $e) {
            $producto = $id ? Producto::find($id) : null;
            $errores = $e->errors;
            require __DIR__ . '/../views/inventario/form.php';
            return;
        }

        header('Location: index.php?route=inventario');
        exit;
    }

    public function actualizarStock(int $id, int $cantidad): void
    {
        Auth::requireRole($this->rolesPermitidos);
        Producto::actualizarCantidad($id, max(0, $cantidad));
        header('Location: index.php?route=inventario');
        exit;
    }

    public function desactivar(int $id): void
    {
        Auth::requireRole($this->rolesPermitidos);
        Producto::desactivar($id);
        header('Location: index.php?route=inventario');
        exit;
    }
}
