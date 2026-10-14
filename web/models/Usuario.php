<?php
/** Ruta: controlvet/web/models/Usuario.php — Cliente del microservicio auth (usuarios) */

class Usuario
{
    /**
     * Autentica contra el servicio auth. Retorna [usuario, token] o null si
     * las credenciales son incorrectas.
     */
    public static function autenticar(string $email, string $password): ?array
    {
        try {
            $r = Api::login($email, $password);
        } catch (ApiValidationException) {
            return null;
        }
        return isset($r['token'], $r['usuario']) ? [$r['usuario'], $r['token']] : null;
    }

    public static function findById(int $id): ?array
    {
        return Api::get('auth', '/usuarios/' . $id);
    }

    public static function all(): array
    {
        return Api::get('auth', '/usuarios') ?? [];
    }

    public static function emailExists(string $email, ?int $excludeId = null): bool
    {
        return (bool)(Api::get('auth', '/usuarios/existe-email', ['email' => $email, 'excluir' => $excludeId])['existe'] ?? false);
    }

    public static function create(array $data): int
    {
        return (int)Api::post('auth', '/usuarios', self::payload($data))['id'];
    }

    public static function update(int $id, array $data): void
    {
        Api::put('auth', '/usuarios/' . $id, self::payload($data));
    }

    public static function setEstado(int $id, string $estado): void
    {
        Api::patch('auth', '/usuarios/' . $id . '/estado', ['estado' => $estado]);
    }

    private static function payload(array $d): array
    {
        return array_intersect_key($d, array_flip(['nombre', 'apellido', 'email', 'password', 'rol_id', 'propietario_id']));
    }
}
