<?php

declare(strict_types=1);

namespace ControlVet\Documentos\Microservicio;

use ControlVet\Documentos\Microservicio\Pdf\ComprobanteCita;
use ControlVet\Documentos\Microservicio\Pdf\FormulaMedica;
use ControlVet\Documentos\Microservicio\Pdf\ResumenConsulta;
use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Security\AuthContext;

/**
 * Compone, genera y registra los documentos PDF del sistema.
 */
final class DocumentoService
{
    public function __construct(
        private readonly DocumentoRepository $repo,
        private readonly FuentesDocumentales $fuentes,
    ) {
    }

    /** @return array{0:string,1:string} [nombre de archivo, bytes PDF] */
    public function comprobanteCita(AuthContext $ctx, int $citaId): array
    {
        $cita = $this->fuentes->cita($citaId) ?? throw HttpException::notFound('Cita no encontrada');
        $datos = $cita + $this->paciente((int)$cita['mascota_id']) + $this->vet((int)$cita['veterinario_id']);

        return $this->emitir($ctx, 'comprobante_cita', $citaId, (int)$cita['propietario_id'], (new ComprobanteCita($datos))->render());
    }

    /** @return array{0:string,1:string} */
    public function resumenConsulta(AuthContext $ctx, int $historiaId): array
    {
        $historia = $this->historiaCompleta($historiaId);
        return $this->emitir($ctx, 'resumen_consulta', $historiaId, (int)$historia['propietario_id'], (new ResumenConsulta($historia))->render());
    }

    /** @return array{0:string,1:string} */
    public function formulaMedica(AuthContext $ctx, int $formulaId): array
    {
        $formula = $this->fuentes->formula($formulaId) ?? throw HttpException::notFound('Fórmula médica no encontrada');
        $historia = $this->historiaCompleta((int)$formula['historia_id']);

        return $this->emitir($ctx, 'formula_medica', $formulaId, (int)$historia['propietario_id'], (new FormulaMedica($formula, $historia))->render());
    }

    public function listar(AuthContext $ctx): array
    {
        if ($ctx->is(AuthContext::CLIENTE)) {
            return $ctx->propietarioId() === null ? [] : $this->repo->porPropietario($ctx->propietarioId());
        }
        return $this->repo->todos();
    }

    /** @return array<string,mixed> */
    private function historiaCompleta(int $historiaId): array
    {
        $h = $this->fuentes->historia($historiaId) ?? throw HttpException::notFound('Historia clínica no encontrada');
        return $h + $this->paciente((int)$h['mascota_id']) + $this->vet((int)$h['veterinario_id']);
    }

    /** @return array<string,mixed> */
    private function paciente(int $mascotaId): array
    {
        $m = $this->fuentes->mascota($mascotaId) ?? throw HttpException::notFound('Mascota no encontrada');
        return [
            'mascota_nombre' => $m['nombre'],
            'especie' => $m['especie'],
            'propietario_nombre' => $m['propietario_nombre'],
            'propietario_apellido' => $m['propietario_apellido'],
        ];
    }

    /** @return array<string,mixed> */
    private function vet(int $veterinarioId): array
    {
        $v = $this->fuentes->veterinario($veterinarioId) ?? ['nombre' => 'No disponible', 'apellido' => ''];
        return ['vet_nombre' => $v['nombre'], 'vet_apellido' => $v['apellido']];
    }

    /** @return array{0:string,1:string} */
    private function emitir(AuthContext $ctx, string $tipo, int $referenciaId, int $propietarioId, string $pdf): array
    {
        // Defensa en profundidad: el servicio de origen ya aplicó el
        // aislamiento, pero se verifica de nuevo antes de registrar.
        $ctx->requireOwnership($propietarioId);
        $nombre = $tipo . '_' . $referenciaId . '.pdf';
        $this->repo->registrar($tipo, $referenciaId, $nombre, $ctx->userId(), $propietarioId);
        return [$nombre, $pdf];
    }
}
