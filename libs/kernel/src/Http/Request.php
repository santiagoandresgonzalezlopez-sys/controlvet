<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Http;

/**
 * Representación inmutable de la petición HTTP entrante.
 */
final class Request
{
    /** @var array<string,string> */
    private array $params = [];

    /**
     * @param array<string,mixed>  $query
     * @param array<string,mixed>  $body
     * @param array<string,string> $headers  Claves en minúscula.
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $body = [],
        public readonly array $headers = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string)$value;
            }
        }
        // Apache + mod_php no expone "Authorization" en $_SERVER: se toma de
        // getallheaders(), que sí incluye todas las cabeceras recibidas.
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $headers[strtolower((string)$name)] = (string)$value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string)$_SERVER['CONTENT_TYPE'];
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $raw = file_get_contents('php://input') ?: '';
        $body = [];
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            $body = is_array($decoded) ? $decoded : [];
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            '/' . trim($path, '/'),
            $_GET,
            $body,
            $headers,
        );
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization') ?? '';
        if (preg_match('/^Bearer\s+(\S+)$/i', $auth, $m)) {
            return $m[1];
        }
        return null;
    }

    public function requestId(): string
    {
        $id = $this->header('x-request-id') ?? '';
        return preg_match('/^[A-Za-z0-9\-]{8,64}$/', $id) ? $id : bin2hex(random_bytes(8));
    }

    /** @param array<string,string> $params */
    public function withParams(array $params): self
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }

    public function param(string $name): string
    {
        return $this->params[$name] ?? '';
    }

    public function intParam(string $name): int
    {
        return (int)$this->param($name);
    }

    public function queryString(string $name, string $default = ''): string
    {
        $v = $this->query[$name] ?? $default;
        return is_scalar($v) ? trim((string)$v) : $default;
    }
}
