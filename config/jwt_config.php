<?php
/**
 * Configuración del sistema de seguridad JWT (JSON Web Token).
 * Ruta: controlvet/config/jwt_config.php
 *
 * IMPORTANTE (uso académico/local): cambie JWT_SECRET por una cadena
 * aleatoria propia antes de usar en un entorno real.
 */

define('JWT_SECRET', 'ControlVet_UTS_2026_ClaveSecretaLocal_CambiarEnProduccion');
define('JWT_ALGO', 'HS256');
define('JWT_EXPIRATION_SECONDS', 3600 * 4); // 4 horas de validez del token
define('JWT_ISSUER', 'ControlVet-XAMPP');
