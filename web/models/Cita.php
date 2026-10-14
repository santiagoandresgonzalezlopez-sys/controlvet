<?php
/** Ruta: controlvet/web/models/Cita.php — Cliente del microservicio citas */

class Cita
{
    public static function all(array $filtros = []): array
    {
        $filtros = array_intersect_key($filtros, array_flip(['fecha', 'veterinario_id', 'estado', 'propietario_id']));
        return Composicion::completar(Api::get('citas', '/citas', $filtros) ?? []);
    }

    public static function delDia(?int $veterinarioId = null): array
    {
        return self::all(['fecha' => date('Y-m-d'), 'veterinario_id' => $veterinarioId]);
    }

    public static function proximas(int $limite = 5, ?int $veterinarioId = null, ?int $propietarioId = null): array
    {
        return Composicion::completar(Api::get('citas', '/citas/proximas', [
            'limite' => $limite, 'veterinario_id' => $veterinarioId, 'propietario_id' => $propietarioId,
        ]) ?? []);
    }

    public static function find(int $id): ?array
    {
        return Composicion::completarUna(Api::get('citas', '/citas/' . $id));
    }

    public static function create(array $d): int
    {
        return (int)Api::post('citas', '/citas', self::payload($d))['id'];
    }

    public static function update(int $id, array $d): void
    {
        Api::put('citas', '/citas/' . $id, self::payload($d));
    }

    public static function cambiarEstado(int $id, string $estado): void
    {
        Api::patch('citas', '/citas/' . $id . '/estado', ['estado' => $estado]);
    }

    public static function veterinarios(): array
    {
        return Api::get('auth', '/veterinarios') ?? [];
    }

    private static function payload(array $d): array
    {
        return array_intersect_key($d, array_flip(['mascota_id', 'veterinario_id', 'fecha', 'hora', 'motivo', 'observaciones']));
    }
}
