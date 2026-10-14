<?php
/** Ruta: controlvet/web/models/Mascota.php — Cliente del microservicio pacientes */

class Mascota
{
    public static function all(string $buscar = ''): array
    {
        return Api::get('pacientes', '/mascotas', ['buscar' => $buscar]) ?? [];
    }

    public static function find(int $id): ?array
    {
        return Api::get('pacientes', '/mascotas/' . $id);
    }

    public static function porPropietario(int $propietarioId): array
    {
        return Api::get('pacientes', '/mascotas', ['propietario_id' => $propietarioId]) ?? [];
    }

    public static function create(array $d): int
    {
        return (int)Api::post('pacientes', '/mascotas', self::payload($d))['id'];
    }

    public static function update(int $id, array $d): void
    {
        Api::put('pacientes', '/mascotas/' . $id, self::payload($d));
    }

    public static function count(): int
    {
        return (int)(Api::get('pacientes', '/resumen')['mascotas'] ?? 0);
    }

    private static function payload(array $d): array
    {
        return array_intersect_key($d, array_flip([
            'nombre', 'especie', 'raza', 'sexo', 'fecha_nacimiento', 'peso_actual', 'color', 'propietario_id', 'observaciones',
        ]));
    }
}
