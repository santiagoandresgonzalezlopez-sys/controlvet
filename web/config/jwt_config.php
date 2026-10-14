<?php
/**
 * Configuración del sistema de seguridad JWT (JSON Web Token).
 * Ruta: controlvet/web/config/jwt_config.php
 *
 * Desde v1.1.0 el token lo emite el microservicio auth; el frontend solo
 * lo valida (firma y expiración) en cada solicitud. El secreto ya NO está
 * en el código: se inyecta con la variable de entorno JWT_SECRET (generada
 * por Ansible o definida en .env, que no se versiona).
 */

$secret = (string)getenv('JWT_SECRET');
if (strlen($secret) < 32) {
    error_log('JWT_SECRET no configurado o demasiado corto');
    http_response_code(500);
    exit('Configuración de seguridad incompleta. Contacte al administrador.');
}

define('JWT_SECRET', $secret);
define('JWT_ALGO', 'HS256');
define('JWT_EXPIRATION_SECONDS', (int)(getenv('JWT_TTL_SECONDS') ?: 3600 * 4));
define('JWT_ISSUER', getenv('JWT_ISSUER') ?: 'ControlVet');
