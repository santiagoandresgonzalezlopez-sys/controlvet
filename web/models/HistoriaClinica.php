<?php
/** Ruta: controlvet/web/models/HistoriaClinica.php — Cliente del microservicio clínica (histórico inmutable) */

class HistoriaClinica
{
    public static function porMascota(int $mascotaId): array
    {
        return Composicion::completar(Api::get('clinica', '/historias', ['mascota_id' => $mascotaId]) ?? []);
    }

    public static function porPropietario(int $propietarioId): array
    {
        return Composicion::completar(Api::get('clinica', '/historias', ['propietario_id' => $propietarioId]) ?? []);
    }

    public static function recientes(int $limite = 5, ?int $veterinarioId = null): array
    {
        return Composicion::completar(Api::get('clinica', '/historias', ['limite' => $limite, 'veterinario_id' => $veterinarioId]) ?? []);
    }

    public static function find(int $id): ?array
    {
        $h = Composicion::completarUna(Api::get('clinica', '/historias/' . $id));
        if ($h) {
            $h['propietario_id'] = (int)$h['propietario_id'];
        }
        return $h;
    }

    /** Registro único e inmutable: no existe método de actualización a propósito. */
    public static function create(array $d): int
    {
        $payload = array_intersect_key($d, array_flip([
            'mascota_id', 'cita_id', 'motivo_consulta', 'peso', 'sintomas', 'diagnostico', 'tratamiento', 'observaciones',
        ]));
        $historia = Api::post('clinica', '/historias', $payload);
        if (($historia['peso_sincronizado'] ?? true) === false) {
            $_SESSION['flash'] = ['tipo' => 'warning', 'mensaje' => 'Consulta registrada. El peso actual de la mascota se actualizará cuando el servicio de pacientes esté disponible.'];
        }
        return (int)$historia['id'];
    }

    public static function crearFormula(int $historiaId, array $d): int
    {
        $payload = array_intersect_key($d, array_flip(['medicamento', 'dosis', 'frecuencia', 'duracion', 'indicaciones']));
        return (int)Api::post('clinica', '/historias/' . $historiaId . '/formulas', $payload)['id'];
    }

    public static function findFormula(int $id): ?array
    {
        return Api::get('clinica', '/formulas/' . $id);
    }

    public static function formulasPorHistoria(int $historiaId): array
    {
        return Api::get('clinica', '/historias/' . $historiaId . '/formulas') ?? [];
    }
}
