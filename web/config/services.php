<?php
/**
 * Ruta: controlvet/web/config/services.php
 * Direcciones de los endpoints de cada microservicio. Por defecto, los
 * publicados por Apache en XAMPP (http://localhost/controlvet/api/<servicio>);
 * se pueden cambiar en web/config/web.env o por variables de entorno.
 */

require_once __DIR__ . '/env.php';

return [
    'auth'       => cv_env('AUTH_URL', 'http://localhost/controlvet/api/auth'),
    'pacientes'  => cv_env('PACIENTES_URL', 'http://localhost/controlvet/api/pacientes'),
    'citas'      => cv_env('CITAS_URL', 'http://localhost/controlvet/api/citas'),
    'clinica'    => cv_env('CLINICA_URL', 'http://localhost/controlvet/api/clinica'),
    'inventario' => cv_env('INVENTARIO_URL', 'http://localhost/controlvet/api/inventario'),
    'documentos' => cv_env('DOCUMENTOS_URL', 'http://localhost/controlvet/api/documentos'),
];
