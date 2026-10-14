<?php
/**
 * Ruta: controlvet/web/index.php
 * Front Controller único del frontend web de ControlVet.
 *
 * Todas las peticiones web pasan por aquí: ?route=xxxx
 * Este archivo carga la infraestructura (config, core, modelos, vistas-
 * modelo) y delega en el Router hacia el ViewModel correspondiente,
 * siguiendo el patrón MVVM solicitado:
 *   Vista       -> views/*.php (solo presentación)
 *   ViewModel   -> viewmodels/*.php (orquesta datos + reglas de UI)
 *   Modelo      -> models/*.php (desde v1.1.0: clientes de la API REST
 *                  de los microservicios; el frontend ya no accede a MySQL)
 */

error_reporting(E_ALL);
ini_set('display_errors', '0'); // Requerimiento 14: no mostrar errores internos de PHP en producción.

require_once __DIR__ . '/config/jwt_config.php';
require_once __DIR__ . '/core/Api.php';
require_once __DIR__ . '/core/Composicion.php';
require_once __DIR__ . '/core/JWT.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/Validator.php';
require_once __DIR__ . '/includes/functions.php';

// Carga de modelos.
foreach (glob(__DIR__ . '/models/*.php') as $modelo) {
    require_once $modelo;
}

// Carga de ViewModels.
foreach (glob(__DIR__ . '/viewmodels/*.php') as $viewModel) {
    require_once $viewModel;
}

Auth::start();

set_exception_handler(function (Throwable $e) {
    error_log('Excepción no controlada: ' . get_class($e) . ': ' . $e->getMessage());
    $noDisponible = $e instanceof ApiUnavailableException;
    http_response_code($noDisponible ? 503 : 500);
    echo '<div style="font-family:sans-serif;max-width:600px;margin:80px auto;text-align:center">'
       . ($noDisponible
           ? '<h2>Servicio temporalmente no disponible</h2><p>Uno de los módulos del sistema no responde. Intente nuevamente en unos minutos.</p>'
           : '<h2>Ocurrió un error inesperado</h2><p>Por favor intente nuevamente. Si el problema persiste contacte al administrador.</p>')
       . '<a href="index.php?route=dashboard">Volver al inicio</a></div>';
});

// Rutas que modifican datos: exigen el token CSRF de la sesión.
const RUTAS_CON_CSRF = [
    'login.procesar', 'usuarios.guardar', 'usuarios.desactivar', 'usuarios.activar',
    'propietarios.guardar', 'propietarios.desactivar', 'mascotas.guardar',
    'citas.guardar', 'citas.estado', 'historias.guardar', 'historias.formula.guardar',
    'inventario.guardar', 'inventario.stock', 'inventario.desactivar',
];

$router = new Router();

// ---------------------------------------------------------------- Auth
$router->add('login', fn() => (new AuthViewModel())->mostrarLogin());
$router->add('login.procesar', fn() => (new AuthViewModel())->procesarLogin($_POST));
$router->add('logout', fn() => (new AuthViewModel())->logout());

// ------------------------------------------------------------ Dashboard
$router->add('dashboard', fn() => (new DashboardViewModel())->mostrar());

// ------------------------------------------------------------- Usuarios
$router->add('usuarios', fn() => (new UsuarioViewModel())->index());
$router->add('usuarios.crear', fn() => (new UsuarioViewModel())->crearForm());
$router->add('usuarios.editar', fn() => (new UsuarioViewModel())->editarForm((int)($_GET['id'] ?? 0)));
$router->add('usuarios.guardar', fn() => (new UsuarioViewModel())->guardar($_POST));
$router->add('usuarios.desactivar', fn() => (new UsuarioViewModel())->desactivar((int)($_GET['id'] ?? 0)));
$router->add('usuarios.activar', fn() => (new UsuarioViewModel())->activar((int)($_GET['id'] ?? 0)));

// ---------------------------------------------------------- Propietarios
$router->add('propietarios', fn() => (new PropietarioViewModel())->index($_GET['buscar'] ?? ''));
$router->add('propietarios.crear', fn() => (new PropietarioViewModel())->crearForm());
$router->add('propietarios.editar', fn() => (new PropietarioViewModel())->editarForm((int)($_GET['id'] ?? 0)));
$router->add('propietarios.detalle', fn() => (new PropietarioViewModel())->detalle((int)($_GET['id'] ?? 0)));
$router->add('propietarios.guardar', fn() => (new PropietarioViewModel())->guardar($_POST));
$router->add('propietarios.desactivar', fn() => (new PropietarioViewModel())->desactivar((int)($_GET['id'] ?? 0)));

// -------------------------------------------------------------- Mascotas
$router->add('mascotas', fn() => (new MascotaViewModel())->index($_GET['buscar'] ?? ''));
$router->add('mascotas.crear', fn() => (new MascotaViewModel())->crearForm());
$router->add('mascotas.editar', fn() => (new MascotaViewModel())->editarForm((int)($_GET['id'] ?? 0)));
$router->add('mascotas.detalle', fn() => (new MascotaViewModel())->detalle((int)($_GET['id'] ?? 0)));
$router->add('mascotas.guardar', fn() => (new MascotaViewModel())->guardar($_POST));

// ----------------------------------------------------------------- Citas
$router->add('citas', fn() => (new CitaViewModel())->index($_GET));
$router->add('citas.crear', fn() => (new CitaViewModel())->crearForm());
$router->add('citas.editar', fn() => (new CitaViewModel())->editarForm((int)($_GET['id'] ?? 0)));
$router->add('citas.guardar', fn() => (new CitaViewModel())->guardar($_POST));
$router->add('citas.estado', fn() => (new CitaViewModel())->cambiarEstado((int)($_GET['id'] ?? 0), $_GET['estado'] ?? ''));
$router->add('citas.pdf', fn() => (new CitaViewModel())->comprobantePdf((int)($_GET['id'] ?? 0)));

// -------------------------------------------------------------- Historias
$router->add('historias.crear', fn() => (new HistoriaViewModel())->crearForm((int)($_GET['mascota_id'] ?? 0)));
$router->add('historias.guardar', fn() => (new HistoriaViewModel())->guardar($_POST));
$router->add('historias.detalle', fn() => (new HistoriaViewModel())->detalle((int)($_GET['id'] ?? 0)));
$router->add('historias.pdf', fn() => (new HistoriaViewModel())->resumenPdf((int)($_GET['id'] ?? 0)));
$router->add('historias.formula.crear', fn() => (new HistoriaViewModel())->crearFormulaForm((int)($_GET['historia_id'] ?? 0)));
$router->add('historias.formula.guardar', fn() => (new HistoriaViewModel())->guardarFormula($_POST));
$router->add('historias.formula.pdf', fn() => (new HistoriaViewModel())->formulaPdf((int)($_GET['id'] ?? 0)));

// ------------------------------------------------------------- Inventario
$router->add('inventario', fn() => (new InventarioViewModel())->index($_GET['buscar'] ?? ''));
$router->add('inventario.crear', fn() => (new InventarioViewModel())->crearForm());
$router->add('inventario.editar', fn() => (new InventarioViewModel())->editarForm((int)($_GET['id'] ?? 0)));
$router->add('inventario.guardar', fn() => (new InventarioViewModel())->guardar($_POST));
$router->add('inventario.stock', fn() => (new InventarioViewModel())->actualizarStock((int)($_GET['id'] ?? 0), (int)($_GET['cantidad'] ?? 0)));
$router->add('inventario.desactivar', fn() => (new InventarioViewModel())->desactivar((int)($_GET['id'] ?? 0)));

// ------------------------------------------------------------- Documentos
$router->add('documentos', fn() => (new DocumentoViewModel())->index());

// --------------------------------------------------------------- Despacho
$route = $_GET['route'] ?? 'login';
if (in_array($route, RUTAS_CON_CSRF, true) && !csrf_valido($_POST['_csrf'] ?? $_GET['_csrf'] ?? null)) {
    http_response_code(403); // Token CSRF ausente o inválido.
    echo '<div style="font-family:sans-serif;max-width:600px;margin:80px auto;text-align:center">'
       . '<h2>La sesión del formulario expiró</h2><p>Por seguridad la acción no se ejecutó. Vuelva a intentarlo.</p>'
       . '<a href="index.php?route=' . (Auth::user() ? 'dashboard' : 'login') . '">Volver</a></div>';
    exit;
}
$router->dispatch($route);
