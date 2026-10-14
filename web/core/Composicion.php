<?php
/**
 * Ruta: controlvet/web/core/Composicion.php
 *
 * Composición de API (patrón API Composition): en el monolito las vistas
 * recibían filas con JOIN de citas/historias + mascotas + propietarios +
 * usuarios. Ahora cada dato vive en un servicio distinto, así que aquí se
 * completan las filas con los nombres que las vistas esperan, conservando
 * exactamente las mismas claves que en v1.0.0.
 */

class Composicion
{
    /** @var array<int,array>|null Caché por petición de veterinarios. */
    private static ?array $veterinarios = null;

    /**
     * Agrega mascota_nombre, especie, propietario_nombre/apellido,
     * vet_nombre/apellido a cada fila con mascota_id y veterinario_id.
     */
    public static function completar(array $filas): array
    {
        if (!$filas) {
            return [];
        }

        $ids = array_values(array_unique(array_map(fn($f) => (int)$f['mascota_id'], $filas)));
        $mascotas = [];
        foreach (Api::get('pacientes', '/mascotas', ['ids' => implode(',', $ids)]) ?? [] as $m) {
            $mascotas[(int)$m['id']] = $m;
        }
        $vets = self::veterinarios();

        return array_map(function (array $f) use ($mascotas, $vets) {
            $m = $mascotas[(int)$f['mascota_id']] ?? [];
            $v = $vets[(int)$f['veterinario_id']] ?? [];
            return $f + [
                'mascota_nombre'       => $m['nombre'] ?? '(no disponible)',
                'especie'              => $m['especie'] ?? '',
                'propietario_nombre'   => $m['propietario_nombre'] ?? '',
                'propietario_apellido' => $m['propietario_apellido'] ?? '',
                'vet_nombre'           => $v['nombre'] ?? '(no disponible)',
                'vet_apellido'         => $v['apellido'] ?? '',
            ];
        }, $filas);
    }

    public static function completarUna(?array $fila): ?array
    {
        return $fila ? self::completar([$fila])[0] : null;
    }

    /** @return array<int,array> Veterinarios (activos e inactivos) indexados por id. */
    private static function veterinarios(): array
    {
        if (self::$veterinarios === null) {
            self::$veterinarios = [];
            foreach (Api::get('auth', '/veterinarios', ['todos' => 1]) ?? [] as $v) {
                self::$veterinarios[(int)$v['id']] = $v;
            }
        }
        return self::$veterinarios;
    }
}
