<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Http;

/**
 * Respuesta HTTP construida por los controladores y emitida por App.
 */
final class Response
{
    /** @param array<string,string> $headers */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = [],
    ) {
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self(
            $status,
            (string)json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION),
            ['Content-Type' => 'application/json; charset=utf-8'],
        );
    }

    public static function created(mixed $data): self
    {
        return self::json($data, 201);
    }

    public static function noContent(): self
    {
        return new self(204, '');
    }

    public static function pdf(string $bytes, string $filename): self
    {
        return new self(200, $bytes, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename) . '"',
        ]);
    }

    public function decoded(): mixed
    {
        return json_decode($this->body, true);
    }

    public function emit(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}
