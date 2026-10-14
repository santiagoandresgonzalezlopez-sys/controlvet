<?php
/**
 * Ruta: controlvet/web/core/Api.php
 *
 * Cliente HTTP del frontend hacia los microservicios. Reemplaza el acceso
 * directo a MySQL que tenía el monolito (core/Database.php): el frontend ya
 * no posee base de datos ni credenciales de base de datos.
 *
 * Cada petición envía el JWT del usuario en sesión; los servicios aplican
 * su propia autorización. Los errores HTTP se traducen a excepciones que
 * los ViewModels saben presentar (422 = errores de formulario, 409 = regla
 * de negocio, 401/403 = sesión/permiso, 5xx = servicio no disponible).
 */

class ApiException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 0, public readonly array $errors = [])
    {
        parent::__construct($message, $status);
    }
}

class ApiValidationException extends ApiException {}
class ApiConflictException extends ApiException {}
class ApiUnavailableException extends ApiException {}

class Api
{
    private static ?string $requestId = null;

    public static function get(string $servicio, string $path, array $query = []): ?array
    {
        $query = array_filter($query, fn($v) => $v !== null && $v !== '');
        $res = self::send($servicio, 'GET', $path . ($query ? '?' . http_build_query($query) : ''));
        return $res['status'] === 404 ? null : self::json($res);
    }

    public static function post(string $servicio, string $path, array $body = []): array
    {
        return self::json(self::send($servicio, 'POST', $path, $body));
    }

    public static function put(string $servicio, string $path, array $body): array
    {
        return self::json(self::send($servicio, 'PUT', $path, $body));
    }

    public static function patch(string $servicio, string $path, array $body = []): array
    {
        return self::json(self::send($servicio, 'PATCH', $path, $body));
    }

    /** POST que retorna el cuerpo binario (PDF del servicio documentos). */
    public static function postBinary(string $servicio, string $path): ?string
    {
        $res = self::send($servicio, 'POST', $path, []);
        if ($res['status'] === 404) {
            return null;
        }
        self::raise($res);
        return $res['body'];
    }

    /** Petición pública (sin JWT): solo para el inicio de sesión. */
    public static function login(string $email, string $password): array
    {
        $res = self::send('auth', 'POST', '/auth/login', ['email' => $email, 'password' => $password], false);
        // 401 aquí significa credenciales incorrectas, no sesión expirada.
        return $res['status'] === 401 ? [] : self::json($res);
    }

    public static function baseUrl(string $servicio): string
    {
        $config = require __DIR__ . '/../config/services.php';
        if (!isset($config[$servicio])) {
            throw new InvalidArgumentException("Servicio desconocido: $servicio");
        }
        return rtrim($config[$servicio], '/');
    }

    private static function send(string $servicio, string $method, string $path, ?array $body = null, bool $auth = true): array
    {
        self::$requestId ??= bin2hex(random_bytes(8));
        $headers = ['Accept: application/json', 'X-Request-Id: ' . self::$requestId];
        if ($auth && ($token = Auth::token())) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        $context = stream_context_create(['http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $body !== null ? json_encode($body) : '',
            'timeout' => 5,
            'ignore_errors' => true,
        ]]);

        $url = self::baseUrl($servicio) . $path;
        $raw = @file_get_contents($url, false, $context);
        $status = 0;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                $status = (int)$m[1];
            }
        }

        if ($raw === false || $status === 0) {
            error_log(json_encode(['level' => 'error', 'service' => 'web', 'request_id' => self::$requestId,
                'message' => 'Servicio no disponible', 'context' => ['servicio' => $servicio, 'path' => $path]]));
            throw new ApiUnavailableException("El servicio $servicio no está disponible.", 503);
        }

        return ['status' => $status, 'body' => (string)$raw];
    }

    private static function json(array $res): array
    {
        self::raise($res);
        $data = json_decode($res['body'], true);
        return is_array($data) ? $data : [];
    }

    private static function raise(array $res): void
    {
        $status = $res['status'];
        if ($status < 400) {
            return;
        }
        $data = json_decode($res['body'], true) ?: [];
        $mensaje = (string)($data['error'] ?? 'Error del servicio');

        match (true) {
            $status === 401 => self::sesionExpirada(),
            $status === 403 => self::prohibido(),
            $status === 422 => throw new ApiValidationException($mensaje, 422, $data['errors'] ?? []),
            $status === 409 => throw new ApiConflictException($mensaje, 409),
            $status >= 500 => throw new ApiUnavailableException($mensaje, $status),
            default => throw new ApiException($mensaje, $status),
        };
    }

    private static function sesionExpirada(): never
    {
        Auth::logout();
        header('Location: index.php?route=login');
        exit;
    }

    private static function prohibido(): never
    {
        http_response_code(403);
        require __DIR__ . '/../views/errors/403.php';
        exit;
    }
}
