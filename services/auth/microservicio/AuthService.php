<?php

declare(strict_types=1);

namespace ControlVet\Auth\Microservicio;

use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Security\Jwt;

/**
 * Reglas de negocio de autenticación y gestión de usuarios.
 */
final class AuthService
{
    /** Hash señuelo: iguala el tiempo de respuesta cuando el correo no existe (evita enumeración de usuarios). */
    private const DUMMY_HASH = '$2y$10$9oXTK3pgbOdymzt5HNeBueQFDTmBbHUD6HgS/wVeoubW7ZbxHctUG';

    public function __construct(
        private readonly UsuarioRepository $repo,
        private readonly Jwt $jwt,
    ) {
    }

    /** @return array{token:string,usuario:array<string,mixed>} */
    public function login(string $email, string $password): array
    {
        $usuario = $this->repo->findActiveByEmail($email);
        $valido = password_verify($password, $usuario['password_hash'] ?? self::DUMMY_HASH);

        if (!$usuario || !$valido) {
            throw HttpException::unauthorized('Correo o contraseña incorrectos.');
        }

        $token = $this->jwt->encode([
            'sub' => (int)$usuario['id'],
            'rol' => $usuario['rol_nombre'],
            'email' => $usuario['email'],
            'propietario_id' => $usuario['propietario_id'] !== null ? (int)$usuario['propietario_id'] : null,
        ]);

        unset($usuario['password_hash']);
        return ['token' => $token, 'usuario' => $usuario];
    }

    /** @param array<string,mixed> $data Datos ya validados. */
    public function crear(array $data): array
    {
        $this->assertReglas($data, null);
        $id = $this->repo->create([
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'password_hash' => password_hash((string)$data['password'], PASSWORD_BCRYPT),
            'rol_id' => (int)$data['rol_id'],
            'propietario_id' => !empty($data['propietario_id']) ? (int)$data['propietario_id'] : null,
        ]);
        return (array)$this->repo->findById($id);
    }

    /** @param array<string,mixed> $data Datos ya validados. */
    public function actualizar(int $id, array $data): array
    {
        if (!$this->repo->findById($id)) {
            throw HttpException::notFound('Usuario no encontrado');
        }
        $this->assertReglas($data, $id);
        $this->repo->update($id, [
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'rol_id' => (int)$data['rol_id'],
            'password_hash' => !empty($data['password']) ? password_hash((string)$data['password'], PASSWORD_BCRYPT) : null,
        ]);
        return (array)$this->repo->findById($id);
    }

    public function cambiarEstado(int $id, string $estado, int $actorId): array
    {
        if (!$this->repo->findById($id)) {
            throw HttpException::notFound('Usuario no encontrado');
        }
        if ($estado === 'inactivo' && $id === $actorId) {
            throw HttpException::conflict('Un administrador no puede desactivar su propia cuenta.');
        }
        $this->repo->setEstado($id, $estado);
        return (array)$this->repo->findById($id);
    }

    /** @param array<string,mixed> $data */
    private function assertReglas(array $data, ?int $excludeId): void
    {
        $errores = [];
        if ($this->repo->emailExists((string)$data['email'], $excludeId)) {
            $errores['email'] = 'Ya existe un usuario registrado con este correo.';
        }
        if (!$this->repo->rolExists((int)$data['rol_id'])) {
            $errores['rol_id'] = 'El rol seleccionado no existe.';
        }
        if ($errores) {
            throw HttpException::unprocessable($errores);
        }
    }
}
