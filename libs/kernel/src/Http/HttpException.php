<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Http;

use RuntimeException;

/**
 * Error controlado que se traduce directamente a una respuesta HTTP.
 * Permite a controladores y servicios cortar el flujo con un código y
 * un mensaje seguros para el cliente (nunca detalles internos).
 */
class HttpException extends RuntimeException
{
    /** @param array<string,string> $errors */
    public function __construct(
        private readonly int $status,
        string $message,
        private readonly array $errors = []
    ) {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public static function badRequest(string $m = 'Solicitud inválida'): self { return new self(400, $m); }
    public static function unauthorized(string $m = 'Token JWT inválido o expirado'): self { return new self(401, $m); }
    public static function forbidden(string $m = 'No autorizado para este recurso'): self { return new self(403, $m); }
    public static function notFound(string $m = 'Recurso no encontrado'): self { return new self(404, $m); }
    public static function conflict(string $m): self { return new self(409, $m); }

    /** @param array<string,string> $errors */
    public static function unprocessable(array $errors, string $m = 'Datos inválidos'): self
    {
        return new self(422, $m, $errors);
    }

    public static function unavailable(string $m = 'Servicio dependiente no disponible'): self { return new self(503, $m); }
}
