<?php

declare(strict_types=1);

namespace ControlVet\E2E;

/**
 * Cliente HTTP mínimo para las pruebas extremo a extremo.
 */
final class Http
{
    public static function url(string $servicio): string
    {
        $env = strtoupper($servicio) . '_URL';
        return rtrim((string)(getenv($env) ?: "http://$servicio"), '/');
    }

    /**
     * @param array<string,mixed>|null $json
     * @param array<string,string>     $headers
     * @return array{status:int,json:mixed,body:string,headers:array<int,string>}
     */
    public static function request(string $method, string $url, ?array $json = null, ?string $token = null, array $headers = [], ?string $form = null, ?string $cookie = null): array
    {
        $h = ['Accept: application/json'];
        if ($token !== null) {
            $h[] = 'Authorization: Bearer ' . $token;
        }
        if ($json !== null) {
            $h[] = 'Content-Type: application/json';
        }
        if ($form !== null) {
            $h[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        if ($cookie !== null) {
            $h[] = 'Cookie: ' . $cookie;
        }
        foreach ($headers as $k => $v) {
            $h[] = "$k: $v";
        }

        $ctx = stream_context_create(['http' => [
            'method' => $method,
            'header' => implode("\r\n", $h),
            'content' => $json !== null ? json_encode($json) : ($form ?? ''),
            'timeout' => 10,
            'ignore_errors' => true,
            'follow_location' => 0,
        ]]);
        $body = @file_get_contents($url, false, $ctx);
        $status = 0;
        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                $status = (int)$m[1];
            }
        }
        return [
            'status' => $status,
            'json' => json_decode((string)$body, true),
            'body' => (string)$body,
            'headers' => $http_response_header ?? [],
        ];
    }

    public static function api(string $method, string $servicio, string $path, ?string $token = null, ?array $json = null): array
    {
        return self::request($method, self::url($servicio) . $path, $json, $token);
    }

    /** @var array<string,string> */
    private static array $tokens = [];

    /** Credenciales de demostración sembradas en la base de datos de CI. */
    public const USUARIOS = [
        'administrador' => ['admin@controlvet.com', 'Admin123'],
        'recepcionista' => ['recepcion@controlvet.com', 'Recep123'],
        'veterinario' => ['veterinario@controlvet.com', 'Vet123'],
        'cliente' => ['cliente@controlvet.com', 'Cliente123'],
    ];

    public static function token(string $rol): string
    {
        if (!isset(self::$tokens[$rol])) {
            [$email, $pass] = self::USUARIOS[$rol];
            $r = self::api('POST', 'auth', '/auth/login', null, ['email' => $email, 'password' => $pass]);
            if ($r['status'] !== 200) {
                throw new \RuntimeException("No fue posible iniciar sesión como $rol: HTTP {$r['status']}");
            }
            self::$tokens[$rol] = $r['json']['token'];
        }
        return self::$tokens[$rol];
    }
}
