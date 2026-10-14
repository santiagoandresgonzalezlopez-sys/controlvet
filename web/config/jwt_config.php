<?php
/**
 * Configuración del sistema de seguridad JWT (JSON Web Token).
 * Ruta: controlvet/web/config/jwt_config.php
 *
 * Desde v1.1.0 el token lo emite el microservicio auth; el frontend solo
 * lo valida (firma y expiración) en cada solicitud. El secreto ya NO está
 * en el código: se lee de web/config/web.env (generado por
 * infra/xampp/instalar.php, no versionado) o de la variable de entorno JWT_SECRET.
 */

require_once __DIR__ . '/env.php';

$secret = cv_env('JWT_SECRET');
if (strlen($secret) < 32) {
    error_log('JWT_SECRET no configurado o demasiado corto');
    http_response_code(500);
    exit('Configuración de seguridad incompleta. Contacte al administrador.');
}

define('JWT_SECRET', $secret);
define('JWT_ALGO', 'HS256');
define('JWT_EXPIRATION_SECONDS', (int)cv_env('JWT_TTL_SECONDS', (string)(3600 * 4)));
define('JWT_ISSUER', cv_env('JWT_ISSUER', 'ControlVet'));
