<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Validation;

use ControlVet\Kernel\Http\HttpException;

/**
 * Reglas de validación reutilizables (misma semántica y mensajes que el
 * Validator original del monolito). Los validadores específicos de cada
 * servicio (carpeta validador/) componen estas reglas.
 */
class Validator
{
    /** @var array<string,string> */
    private array $errores = [];

    /** @param array<string,mixed> $data */
    public function __construct(private readonly array $data)
    {
    }

    public function required(string $field, string $label): self
    {
        if (!isset($this->data[$field]) || trim((string)$this->data[$field]) === '') {
            $this->fail($field, "El campo $label es obligatorio.");
        }
        return $this;
    }

    public function email(string $field, string $label = 'correo electrónico'): self
    {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->fail($field, "El $label no tiene un formato válido.");
        }
        return $this;
    }

    public function numeric(string $field, string $label): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !is_numeric($this->data[$field])) {
            $this->fail($field, "El campo $label debe ser numérico.");
        }
        return $this;
    }

    public function integer(string $field, string $label, int $min = 0): self
    {
        $v = $this->data[$field] ?? null;
        if ($v !== null && $v !== '' && (filter_var($v, FILTER_VALIDATE_INT) === false || (int)$v < $min)) {
            $this->fail($field, "El campo $label debe ser un entero mayor o igual a $min.");
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label): self
    {
        if (isset($this->data[$field]) && mb_strlen((string)$this->data[$field]) < $min) {
            $this->fail($field, "El campo $label debe tener al menos $min caracteres.");
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label): self
    {
        if (isset($this->data[$field]) && mb_strlen((string)$this->data[$field]) > $max) {
            $this->fail($field, "El campo $label no puede superar $max caracteres.");
        }
        return $this;
    }

    public function date(string $field, string $label): self
    {
        if (!empty($this->data[$field])) {
            $d = \DateTime::createFromFormat('Y-m-d', (string)$this->data[$field]);
            if (!$d || $d->format('Y-m-d') !== $this->data[$field]) {
                $this->fail($field, "La fecha de $label no es válida.");
            }
        }
        return $this;
    }

    public function time(string $field, string $label): self
    {
        if (!empty($this->data[$field]) && !preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', (string)$this->data[$field])) {
            $this->fail($field, "La hora de $label no es válida.");
        }
        return $this;
    }

    /** @param array<int,string> $allowed */
    public function in(string $field, array $allowed, string $label): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !in_array($this->data[$field], $allowed, true)) {
            $this->fail($field, "El valor de $label no es válido.");
        }
        return $this;
    }

    public function custom(bool $condicion, string $field, string $mensaje): self
    {
        if (!$condicion) {
            $this->fail($field, $mensaje);
        }
        return $this;
    }

    public function passes(): bool
    {
        return $this->errores === [];
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errores;
    }

    /** Lanza 422 con el detalle por campo si hay errores. */
    public function validate(): void
    {
        if (!$this->passes()) {
            throw HttpException::unprocessable($this->errores);
        }
    }

    private function fail(string $field, string $message): void
    {
        // Se conserva el primer error por campo (mensaje más relevante).
        $this->errores[$field] ??= $message;
    }
}
