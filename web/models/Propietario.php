<?php
/** Ruta: controlvet/web/models/Propietario.php — Cliente del microservicio pacientes */

class Propietario
{
    public static function all(string $buscar = ''): array
    {
        return Api::get('pacientes', '/propietarios', ['buscar' => $buscar]) ?? [];
    }

    public static function find(int $id): ?array
    {
        return Api::get('pacientes', '/propietarios/' . $id);
    }

    public static function documentoExists(string $documento, ?int $excludeId = null): bool
    {
        return (bool)(Api::get('pacientes', '/propietarios/existe-documento', ['documento' => $documento, 'excluir' => $excludeId])['existe'] ?? false);
    }

    public static function create(array $d): int
    {
        return (int)Api::post('pacientes', '/propietarios', self::payload($d))['id'];
    }

    public static function update(int $id, array $d): void
    {
        Api::put('pacientes', '/propietarios/' . $id, self::payload($d));
    }

    /** Desactivación lógica; nunca eliminación física. False si tiene mascotas activas. */
    public static function desactivar(int $id): bool
    {
        try {
            Api::patch('pacientes', '/propietarios/' . $id . '/desactivar');
            return true;
        } catch (ApiConflictException) {
            return false;
        }
    }

    public static function mascotas(int $id): array
    {
        return Api::get('pacientes', '/propietarios/' . $id . '/mascotas') ?? [];
    }

    public static function count(): int
    {
        return (int)(Api::get('pacientes', '/resumen')['propietarios'] ?? 0);
    }

    private static function payload(array $d): array
    {
        return array_intersect_key($d, array_flip(['nombre', 'apellido', 'documento', 'telefono', 'email', 'direccion']));
    }
}
