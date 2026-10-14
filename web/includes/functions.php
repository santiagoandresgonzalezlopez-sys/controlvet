<?php
/** Ruta: controlvet/includes/functions.php — funciones auxiliares compartidas */

function flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/**
 * Protección CSRF: token aleatorio por sesión, incluido en cada formulario
 * POST y en cada enlace que modifica datos, y verificado en index.php.
 */
function csrf_token(): string
{
    Auth::start();
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function csrf_valido(?string $token): bool
{
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

function menuPorRol(string $rol): array
{
    $menus = [
        'administrador' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'bi-speedometer2'],
            ['label' => 'Usuarios', 'route' => 'usuarios', 'icon' => 'bi-people'],
            ['label' => 'Propietarios', 'route' => 'propietarios', 'icon' => 'bi-person-vcard'],
            ['label' => 'Mascotas', 'route' => 'mascotas', 'icon' => 'bi-heart-pulse'],
            ['label' => 'Citas', 'route' => 'citas', 'icon' => 'bi-calendar-event'],
            ['label' => 'Inventario', 'route' => 'inventario', 'icon' => 'bi-box-seam'],
            ['label' => 'Documentos', 'route' => 'documentos', 'icon' => 'bi-file-earmark-pdf'],
        ],
        'recepcionista' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'bi-speedometer2'],
            ['label' => 'Propietarios', 'route' => 'propietarios', 'icon' => 'bi-person-vcard'],
            ['label' => 'Mascotas', 'route' => 'mascotas', 'icon' => 'bi-heart-pulse'],
            ['label' => 'Citas', 'route' => 'citas', 'icon' => 'bi-calendar-event'],
            ['label' => 'Documentos', 'route' => 'documentos', 'icon' => 'bi-file-earmark-pdf'],
        ],
        'veterinario' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'bi-speedometer2'],
            ['label' => 'Pacientes', 'route' => 'mascotas', 'icon' => 'bi-heart-pulse'],
            ['label' => 'Citas', 'route' => 'citas', 'icon' => 'bi-calendar-event'],
            ['label' => 'Documentos', 'route' => 'documentos', 'icon' => 'bi-file-earmark-pdf'],
        ],
        'cliente' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'bi-speedometer2'],
            ['label' => 'Mis citas', 'route' => 'citas', 'icon' => 'bi-calendar-event'],
            ['label' => 'Mis documentos', 'route' => 'documentos', 'icon' => 'bi-file-earmark-pdf'],
        ],
    ];

    return $menus[$rol] ?? [];
}

function badgeEstadoCita(string $estado): string
{
    $clases = [
        'programada' => 'secondary',
        'confirmada' => 'info',
        'atendida'   => 'success',
        'cancelada'  => 'danger',
    ];
    return $clases[$estado] ?? 'secondary';
}
