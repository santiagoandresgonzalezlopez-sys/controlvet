<?php
/**
 * Ruta: controlvet/core/Auth.php
 *
 * Middleware de autenticación y control de acceso por rol.
 * Combina sesión PHP (experiencia web normal) + validación de JWT
 * (integridad/expiración) en cada solicitud a un módulo protegido.
 */

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(array $usuario): void
    {
        self::start();
        session_regenerate_id(true); // Previene fijación de sesión.

        $_SESSION['usuario'] = [
            'id'        => $usuario['id'],
            'nombre'    => $usuario['nombre'] . ' ' . $usuario['apellido'],
            'email'     => $usuario['email'],
            'rol_id'    => (int)$usuario['rol_id'],
            'rol'       => $usuario['rol_nombre'],
            'propietario_id' => $usuario['propietario_id'],
        ];

        // Emisión del JWT como capa adicional de seguridad.
        $token = JWT::generate([
            'sub'  => $usuario['id'],
            'rol'  => $usuario['rol_nombre'],
            'email' => $usuario['email'],
        ]);

        $_SESSION['jwt'] = $token;

        // También se entrega como cookie HttpOnly para uso en llamadas a la API (fetch).
        setcookie('controlvet_jwt', $token, [
            'expires'  => time() + JWT_EXPIRATION_SECONDS,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
        setcookie('controlvet_jwt', '', time() - 3600, '/');
    }

    public static function check(): bool
    {
        self::start();

        if (!isset($_SESSION['usuario'], $_SESSION['jwt'])) {
            return false;
        }

        // Validación de integridad/expiración del JWT en cada solicitud.
        $claims = JWT::validate($_SESSION['jwt']);
        if ($claims === null || $claims['sub'] !== $_SESSION['usuario']['id']) {
            self::logout();
            return false;
        }

        return true;
    }

    public static function user(): ?array
    {
        self::start();
        return $_SESSION['usuario'] ?? null;
    }

    public static function role(): ?string
    {
        return self::user()['rol'] ?? null;
    }

    /**
     * Protege una ruta: exige sesión válida y, opcionalmente, uno de los
     * roles permitidos. Redirige (o responde 403) en caso contrario.
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: index.php?route=login');
            exit;
        }
    }

    public static function requireRole(array $rolesPermitidos): void
    {
        self::requireLogin();
        if (!in_array(self::role(), $rolesPermitidos, true)) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }
    }

    /**
     * Autenticación exclusivamente por JWT (Bearer token) para endpoints
     * de API/AJAX, independiente de la sesión PHP.
     */
    public static function requireApiToken(array $rolesPermitidos = []): array
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ($_COOKIE['controlvet_jwt'] ?? '');
        $token = trim(str_ireplace('Bearer', '', $authHeader));

        $claims = JWT::validate($token);
        if ($claims === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Token JWT inválido o expirado']);
            exit;
        }

        if (!empty($rolesPermitidos) && !in_array($claims['rol'], $rolesPermitidos, true)) {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado para este recurso']);
            exit;
        }

        return $claims;
    }
}
