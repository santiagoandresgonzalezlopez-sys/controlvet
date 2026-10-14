<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Http;

use ControlVet\Kernel\Log\Logger;

/**
 * Cliente HTTP para la comunicación síncrona entre microservicios.
 *
 * - Reenvía el JWT del usuario que originó la petición, de modo que el
 *   servicio destino aplica SUS PROPIAS reglas de autorización y
 *   aislamiento (no existe una "cuenta de servicio" con privilegios).
 * - Propaga X-Request-Id para correlacionar logs entre servicios.
 * - Traduce fallos de red en 503 para que el llamador degrade de forma
 *   controlada en lugar de exponer errores internos.
 */
class ServiceClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $token,
        private readonly string $requestId,
        private readonly Logger $logger,
        private readonly float $timeoutSeconds = 3.0,
    ) {
    }

    /** @param array<string,scalar> $query */
    public function get(string $path, array $query = []): ?array
    {
        $qs = $query ? '?' . http_build_query($query) : '';
        return $this->send('GET', $path . $qs);
    }

    /** @param array<string,mixed> $body */
    public function patch(string $path, array $body): ?array
    {
        return $this->send('PATCH', $path, $body);
    }

    /**
     * @param array<string,mixed>|null $body
     * @return array<mixed>|null  null cuando el recurso no existe (404).
     */
    private function send(string $method, string $path, ?array $body = null): ?array
    {
        $headers = [
            'Accept: application/json',
            'X-Request-Id: ' . $this->requestId,
        ];
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        $context = stream_context_create(['http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $body !== null ? json_encode($body) : '',
            'timeout' => $this->timeoutSeconds,
            'ignore_errors' => true,
        ]]);

        $url = rtrim($this->baseUrl, '/') . $path;
        $raw = @file_get_contents($url, false, $context);
        $status = $this->statusFrom($http_response_header ?? []);

        if ($raw === false || $status === 0) {
            $this->logger->error('Fallo de comunicación con servicio dependiente', ['url' => $url]);
            throw HttpException::unavailable();
        }
        if ($status === 404) {
            return null;
        }
        if ($status === 401 || $status === 403) {
            throw new HttpException($status, $status === 401 ? 'Token JWT inválido o expirado' : 'No autorizado para este recurso');
        }
        if ($status >= 400) {
            $this->logger->warning('Servicio dependiente respondió con error', ['url' => $url, 'status' => $status]);
            throw HttpException::unavailable();
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /** @param array<int,string> $headers */
    private function statusFrom(array $headers): int
    {
        foreach ($headers as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                $status = (int)$m[1]; // Se conserva el último (tras redirecciones).
            }
        }
        return $status ?? 0;
    }
}
