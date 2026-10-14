<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Security;

use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Http\Request;

/**
 * Identidad del usuario autenticado, derivada EXCLUSIVAMENTE del JWT
 * validado. Cada servicio la usa para autorizar y aislar datos; nunca se
 * confía en identificadores de usuario enviados en el cuerpo o la URL.
 */
final class AuthContext
{
    public const ADMIN = 'administrador';
    public const RECEPCION = 'recepcionista';
    public const VETERINARIO = 'veterinario';
    public const CLIENTE = 'cliente';

    /** @param array<string,mixed> $claims */
    private function __construct(private readonly array $claims, public readonly string $token)
    {
    }

    public static function fromRequest(Request $request, Jwt $jwt): self
    {
        $token = $request->bearerToken();
        $claims = $jwt->decode($token);
        if ($claims === null || !isset($claims['sub'], $claims['rol'])) {
            throw HttpException::unauthorized();
        }
        return new self($claims, (string)$token);
    }

    /**
     * Construye un contexto a partir de claims ya verificados (pruebas
     * unitarias de reglas de negocio). Nunca usar con datos del cliente.
     *
     * @param array<string,mixed> $claims
     */
    public static function fromVerifiedClaims(array $claims, string $token = ''): self
    {
        return new self($claims, $token);
    }

    public function userId(): int
    {
        return (int)$this->claims['sub'];
    }

    public function role(): string
    {
        return (string)$this->claims['rol'];
    }

    public function email(): string
    {
        return (string)($this->claims['email'] ?? '');
    }

    public function propietarioId(): ?int
    {
        $id = $this->claims['propietario_id'] ?? null;
        return $id === null ? null : (int)$id;
    }

    public function is(string ...$roles): bool
    {
        return in_array($this->role(), $roles, true);
    }

    public function requireRole(string ...$roles): self
    {
        if (!$this->is(...$roles)) {
            throw HttpException::forbidden();
        }
        return $this;
    }

    /**
     * Aislamiento de datos del rol cliente: solo puede ver recursos
     * de su propio propietario. Para otros roles no aplica restricción.
     */
    public function requireOwnership(?int $propietarioIdRecurso): void
    {
        if ($this->is(self::CLIENTE) && ($this->propietarioId() === null || $this->propietarioId() !== $propietarioIdRecurso)) {
            throw HttpException::forbidden();
        }
    }
}
