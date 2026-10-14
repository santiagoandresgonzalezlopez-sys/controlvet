<?php

declare(strict_types=1);

namespace ControlVet\Clinica\Microservicio;

use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Security\AuthContext;

/**
 * Reglas de negocio de la historia clínica (registro inmutable) y de las
 * fórmulas médicas derivadas de ella.
 */
final class ClinicaService
{
    public function __construct(
        private readonly HistoriaRepository $repo,
        private readonly DirectorioPacientes $pacientes,
    ) {
    }

    /** @param array<string,mixed> $filtros */
    public function listar(AuthContext $ctx, array $filtros, int $limite): array
    {
        $filtros = array_map('intval', array_filter(
            array_intersect_key($filtros, array_flip(['mascota_id', 'propietario_id', 'veterinario_id'])),
            fn($v) => $v !== '' && $v !== null,
        ));
        if ($ctx->is(AuthContext::CLIENTE)) {
            if ($ctx->propietarioId() === null) {
                return [];
            }
            $filtros['propietario_id'] = $ctx->propietarioId();
        }
        return $this->repo->buscar($filtros, $limite);
    }

    public function ver(AuthContext $ctx, int $id): array
    {
        $h = $this->repo->find($id) ?? throw HttpException::notFound('Historia clínica no encontrada');
        $ctx->requireOwnership((int)$h['propietario_id']);
        return $h;
    }

    /**
     * Registro único e inmutable de una consulta. El veterinario autor es
     * siempre el usuario autenticado (no se acepta desde el cuerpo).
     *
     * @param array<string,mixed> $d Datos ya validados.
     */
    public function registrar(AuthContext $ctx, array $d): array
    {
        $mascota = $this->pacientes->mascota((int)$d['mascota_id']);
        if (!$mascota || $mascota['estado'] !== 'activo') {
            throw HttpException::unprocessable(['mascota_id' => 'El paciente no existe o está inactivo.']);
        }

        $peso = ($d['peso'] ?? '') !== '' ? (float)$d['peso'] : null;
        $id = $this->repo->create([
            'mascota_id' => (int)$d['mascota_id'],
            'propietario_id' => (int)$mascota['propietario_id'],
            'veterinario_id' => $ctx->userId(),
            'cita_id' => !empty($d['cita_id']) ? (int)$d['cita_id'] : null,
            'motivo_consulta' => trim((string)$d['motivo_consulta']),
            'peso' => $peso,
            'sintomas' => self::opcional($d, 'sintomas'),
            'diagnostico' => trim((string)$d['diagnostico']),
            'tratamiento' => self::opcional($d, 'tratamiento'),
            'observaciones' => self::opcional($d, 'observaciones'),
        ]);

        // El peso actual de la mascota pertenece al servicio pacientes: se
        // sincroniza por API. Si falla, la historia ya quedó registrada (es la
        // fuente de verdad clínica) y se informa para reintento.
        $sincronizado = $peso === null || $this->pacientes->actualizarPeso((int)$d['mascota_id'], $peso);

        return (array)$this->repo->find($id) + ['peso_sincronizado' => $sincronizado];
    }

    public function formulas(AuthContext $ctx, int $historiaId): array
    {
        $this->ver($ctx, $historiaId);
        return $this->repo->formulas($historiaId);
    }

    public function formula(AuthContext $ctx, int $id): array
    {
        $f = $this->repo->formula($id) ?? throw HttpException::notFound('Fórmula médica no encontrada');
        $ctx->requireOwnership((int)$f['propietario_id']);
        return $f;
    }

    /** @param array<string,mixed> $d Datos ya validados. */
    public function crearFormula(AuthContext $ctx, int $historiaId, array $d): array
    {
        $this->ver($ctx, $historiaId);
        $id = $this->repo->createFormula($historiaId, [
            'medicamento' => trim((string)$d['medicamento']),
            'dosis' => trim((string)$d['dosis']),
            'frecuencia' => trim((string)$d['frecuencia']),
            'duracion' => trim((string)$d['duracion']),
            'indicaciones' => self::opcional($d, 'indicaciones'),
        ]);
        return (array)$this->repo->formula($id);
    }

    /** @param array<string,mixed> $d */
    private static function opcional(array $d, string $campo): ?string
    {
        $v = trim((string)($d[$campo] ?? ''));
        return $v === '' ? null : $v;
    }
}
