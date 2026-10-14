<?php
/**
 * Ruta: controlvet/core/Validator.php
 * Utilidades de validación de formularios (frontend + backend).
 */

class Validator
{
    private array $errores = [];

    public function required(array $data, string $field, string $label): self
    {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            $this->errores[$field] = "El campo $label es obligatorio.";
        }
        return $this;
    }

    public function email(array $data, string $field, string $label = 'correo electrónico'): self
    {
        if (!empty($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errores[$field] = "El $label no tiene un formato válido.";
        }
        return $this;
    }

    public function numeric(array $data, string $field, string $label): self
    {
        if (isset($data[$field]) && $data[$field] !== '' && !is_numeric($data[$field])) {
            $this->errores[$field] = "El campo $label debe ser numérico.";
        }
        return $this;
    }

    public function minLength(array $data, string $field, int $min, string $label): self
    {
        if (isset($data[$field]) && strlen((string)$data[$field]) < $min) {
            $this->errores[$field] = "El campo $label debe tener al menos $min caracteres.";
        }
        return $this;
    }

    public function date(array $data, string $field, string $label): self
    {
        if (!empty($data[$field])) {
            $d = DateTime::createFromFormat('Y-m-d', $data[$field]);
            if (!$d || $d->format('Y-m-d') !== $data[$field]) {
                $this->errores[$field] = "La fecha de $label no es válida.";
            }
        }
        return $this;
    }

    public function custom(bool $condicion, string $field, string $mensaje): self
    {
        if (!$condicion) {
            $this->errores[$field] = $mensaje;
        }
        return $this;
    }

    public function passes(): bool
    {
        return empty($this->errores);
    }

    public function errors(): array
    {
        return $this->errores;
    }
}

/** Saneamiento de salida para mitigar XSS al imprimir en las vistas. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
