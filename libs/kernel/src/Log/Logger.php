<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Log;

/**
 * Registro estructurado (una línea JSON por evento) hacia stderr, que en
 * XAMPP termina en el error.log de Apache (C:\xampp\apache\logs\error.log).
 * Nunca se registran contraseñas ni tokens.
 */
class Logger
{
    private const SENSITIVE = ['password', 'password_hash', 'token', 'authorization', 'jwt'];

    /** @var resource */
    private $stream;

    /** @param resource|null $stream */
    public function __construct(
        private readonly string $service,
        private string $requestId = '-',
        $stream = null,
    ) {
        $this->stream = $stream ?? fopen('php://stderr', 'wb');
    }

    public function withRequestId(string $requestId): self
    {
        $clone = clone $this;
        $clone->requestId = $requestId;
        return $clone;
    }

    /** @param array<string,mixed> $context */
    public function info(string $message, array $context = []): void { $this->log('info', $message, $context); }
    /** @param array<string,mixed> $context */
    public function warning(string $message, array $context = []): void { $this->log('warning', $message, $context); }
    /** @param array<string,mixed> $context */
    public function error(string $message, array $context = []): void { $this->log('error', $message, $context); }

    /** @param array<string,mixed> $context */
    public function log(string $level, string $message, array $context = []): void
    {
        foreach ($context as $key => $_) {
            if (in_array(strtolower((string)$key), self::SENSITIVE, true)) {
                $context[$key] = '***';
            }
        }
        fwrite($this->stream, json_encode([
            'ts' => gmdate('Y-m-d\TH:i:s\Z'),
            'level' => $level,
            'service' => $this->service,
            'request_id' => $this->requestId,
            'message' => $message,
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
}
