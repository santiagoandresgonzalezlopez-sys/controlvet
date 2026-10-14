<?php

declare(strict_types=1);

namespace ControlVet\Pacientes\Microservicio;

use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Security\AuthContext;

/**
 * Reglas de negocio de propietarios y mascotas.
 */
final class PacientesService
{
    public function __construct(private readonly PacientesRepository $repo)
    {
    }

    public function propietario(AuthContext $ctx, int $id): array
    {
        $p = $this->repo->propietario($id) ?? throw HttpException::notFound('Propietario no encontrado');
        $ctx->requireOwnership((int)$p['id']);
        return $p;
    }

    /** @param array<string,mixed> $d Datos ya validados. */
    public function guardarPropietario(?int $id, array $d): array
    {
        if ($id !== null && !$this->repo->propietario($id)) {
            throw HttpException::notFound('Propietario no encontrado');
        }
        if ($this->repo->documentoExists((string)$d['documento'], $id)) {
            throw HttpException::unprocessable(['documento' => 'Ya existe un propietario registrado con este documento.']);
        }
        if ($id === null) {
            $id = $this->repo->crearPropietario($d);
        } else {
            $this->repo->actualizarPropietario($id, $d);
        }
        return (array)$this->repo->propietario($id);
    }

    /** Desactivación lógica; nunca eliminación física. */
    public function desactivarPropietario(int $id): array
    {
        if (!$this->repo->propietario($id)) {
            throw HttpException::notFound('Propietario no encontrado');
        }
        if ($this->repo->contarMascotasActivas($id) > 0) {
            throw HttpException::conflict('No es posible desactivar: el propietario tiene mascotas activas asociadas.');
        }
        $this->repo->desactivarPropietario($id);
        return (array)$this->repo->propietario($id);
    }

    /**
     * Aislamiento: un cliente solo obtiene las mascotas de su propietario,
     * sin importar los filtros que envíe.
     *
     * @param array<int,int> $ids
     */
    public function mascotas(AuthContext $ctx, string $buscar, ?int $propietarioId, array $ids): array
    {
        if ($ctx->is(AuthContext::CLIENTE)) {
            if ($ctx->propietarioId() === null) {
                return [];
            }
            $propietarioId = $ctx->propietarioId();
        }
        return $this->repo->mascotas($buscar, $propietarioId, $ids);
    }

    public function mascota(AuthContext $ctx, int $id): array
    {
        $m = $this->repo->mascota($id) ?? throw HttpException::notFound('Mascota no encontrada');
        $ctx->requireOwnership((int)$m['propietario_id']);
        return $m;
    }

    /** @param array<string,mixed> $d Datos ya validados. */
    public function guardarMascota(?int $id, array $d): array
    {
        if ($id !== null && !$this->repo->mascota($id)) {
            throw HttpException::notFound('Mascota no encontrada');
        }
        $propietario = $this->repo->propietario((int)$d['propietario_id']);
        if (!$propietario || $propietario['estado'] !== 'activo') {
            throw HttpException::unprocessable(['propietario_id' => 'No es posible registrar una mascota sin propietario activo.']);
        }
        if ($id === null) {
            $id = $this->repo->crearMascota($d);
        } else {
            $this->repo->actualizarMascota($id, $d);
        }
        return (array)$this->repo->mascota($id);
    }

    /** Invocado por el servicio clínica al registrar una consulta con peso. */
    public function actualizarPeso(int $id, float $peso): array
    {
        if (!$this->repo->mascota($id)) {
            throw HttpException::notFound('Mascota no encontrada');
        }
        $this->repo->actualizarPeso($id, $peso);
        return (array)$this->repo->mascota($id);
    }

    /** @return array{propietarios:int,mascotas:int} */
    public function resumen(): array
    {
        return ['propietarios' => $this->repo->contarPropietarios(), 'mascotas' => $this->repo->contarMascotas()];
    }
}
