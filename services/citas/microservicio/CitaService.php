<?php

declare(strict_types=1);

namespace ControlVet\Citas\Microservicio;

use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Security\AuthContext;

/**
 * Reglas de negocio de la agenda de citas.
 */
final class CitaService
{
    public const ESTADOS = ['programada', 'confirmada', 'atendida', 'cancelada'];

    public function __construct(
        private readonly CitaRepository $repo,
        private readonly DirectorioClinico $directorio,
    ) {
    }

    /**
     * Aplica el alcance por rol: el veterinario ve su agenda y el cliente
     * las citas de sus mascotas, sin importar los filtros que envíe.
     *
     * @param array<string,mixed> $filtros
     */
    public function listar(AuthContext $ctx, array $filtros): array
    {
        $filtros = array_intersect_key($filtros, array_flip(['fecha', 'veterinario_id', 'estado', 'propietario_id']));
        if ($ctx->is(AuthContext::VETERINARIO)) {
            $filtros['veterinario_id'] = $ctx->userId();
        } elseif ($ctx->is(AuthContext::CLIENTE)) {
            if ($ctx->propietarioId() === null) {
                return [];
            }
            $filtros['propietario_id'] = $ctx->propietarioId();
        }
        return $this->repo->buscar($filtros);
    }

    public function proximas(AuthContext $ctx, int $limite, ?int $veterinarioId, ?int $propietarioId): array
    {
        if ($ctx->is(AuthContext::VETERINARIO)) {
            $veterinarioId = $ctx->userId();
        } elseif ($ctx->is(AuthContext::CLIENTE)) {
            if ($ctx->propietarioId() === null) {
                return [];
            }
            $propietarioId = $ctx->propietarioId();
        }
        return $this->repo->proximas($limite, $veterinarioId, $propietarioId);
    }

    public function ver(AuthContext $ctx, int $id): array
    {
        $cita = $this->repo->find($id) ?? throw HttpException::notFound('Cita no encontrada');
        $ctx->requireOwnership((int)$cita['propietario_id']);
        return $cita;
    }

    /** @param array<string,mixed> $d Datos ya validados por CitaValidador. */
    public function guardar(AuthContext $ctx, ?int $id, array $d): array
    {
        if ($id !== null && !$this->repo->find($id)) {
            throw HttpException::notFound('Cita no encontrada');
        }

        $mascota = $this->directorio->mascota((int)$d['mascota_id']);
        $vet = $this->directorio->veterinario((int)$d['veterinario_id']);
        $hora = substr((string)$d['hora'], 0, 5) . ':00';

        $errores = [];
        if (!$mascota || $mascota['estado'] !== 'activo') {
            $errores['mascota_id'] = 'No es posible registrar una cita sin paciente.';
        }
        if (!$vet || $vet['estado'] !== 'activo') {
            $errores['veterinario_id'] = 'No es posible registrar una cita sin veterinario.';
        } elseif ($this->repo->horarioOcupado((int)$d['veterinario_id'], (string)$d['fecha'], $hora, $id)) {
            $errores['hora'] = 'El veterinario ya tiene una cita asignada en esa fecha y hora.';
        }
        if ($errores) {
            throw HttpException::unprocessable($errores);
        }

        $datos = [
            'mascota_id' => (int)$d['mascota_id'],
            // Copia local del dueño al momento de agendar: permite aislar los
            // datos del cliente sin consultar otro servicio en cada lectura.
            'propietario_id' => (int)$mascota['propietario_id'],
            'veterinario_id' => (int)$d['veterinario_id'],
            'fecha' => (string)$d['fecha'],
            'hora' => $hora,
            'motivo' => trim((string)$d['motivo']),
            'observaciones' => trim((string)($d['observaciones'] ?? '')),
            'creado_por' => $ctx->userId(),
        ];

        if ($id === null) {
            $id = $this->repo->create($datos);
        } else {
            $this->repo->update($id, $datos);
        }
        return (array)$this->repo->find($id);
    }

    public function cambiarEstado(AuthContext $ctx, int $id, string $estado): array
    {
        $cita = $this->repo->find($id) ?? throw HttpException::notFound('Cita no encontrada');

        // El veterinario solo puede marcar como atendidas sus propias citas.
        if ($ctx->is(AuthContext::VETERINARIO) && ((int)$cita['veterinario_id'] !== $ctx->userId() || $estado !== 'atendida')) {
            throw HttpException::forbidden('El veterinario solo puede marcar como atendidas sus propias citas.');
        }

        $this->repo->cambiarEstado($id, $estado);
        return (array)$this->repo->find($id);
    }
}
