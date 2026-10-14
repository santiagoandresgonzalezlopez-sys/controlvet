<?php
/**
 * Ruta: controlvet/viewmodels/DashboardViewModel.php
 * Prepara los indicadores según el rol autenticado y selecciona la vista.
 */

class DashboardViewModel
{
    public function mostrar(): void
    {
        Auth::requireLogin();
        $usuario = Auth::user();

        switch ($usuario['rol']) {
            case 'administrador':
                $datos = [
                    'total_usuarios'     => count(Usuario::all()),
                    'total_propietarios' => Propietario::count(),
                    'total_mascotas'     => Mascota::count(),
                    'citas_proximas'     => Cita::proximas(5),
                    'total_productos'    => Producto::count(),
                    'stock_bajo'         => Producto::stockBajo(),
                ];
                require __DIR__ . '/../views/dashboard/admin.php';
                break;

            case 'recepcionista':
                $datos = [
                    'citas_hoy'          => Cita::delDia(),
                    'citas_proximas'     => Cita::proximas(5),
                    'total_propietarios' => Propietario::count(),
                    'total_mascotas'     => Mascota::count(),
                ];
                require __DIR__ . '/../views/dashboard/recepcionista.php';
                break;

            case 'veterinario':
                $datos = [
                    'citas_hoy'      => Cita::delDia($usuario['id']),
                    'citas_proximas' => Cita::proximas(5, $usuario['id']),
                    'pacientes_recientes' => HistoriaClinica::recientes(5, $usuario['id']),
                ];
                require __DIR__ . '/../views/dashboard/veterinario.php';
                break;

            case 'cliente':
                $propietarioId = $usuario['propietario_id'];
                $datos = [
                    'mascotas'        => $propietarioId ? Mascota::porPropietario($propietarioId) : [],
                    'citas_proximas'  => $propietarioId ? Cita::proximas(5, null, $propietarioId) : [],
                    'historial'       => $propietarioId ? HistoriaClinica::porPropietario($propietarioId) : [],
                    'documentos'      => $propietarioId ? Documento::porPropietario($propietarioId) : [],
                ];
                require __DIR__ . '/../views/dashboard/cliente.php';
                break;

            default:
                http_response_code(403);
                require __DIR__ . '/../views/errors/403.php';
        }
    }
}
