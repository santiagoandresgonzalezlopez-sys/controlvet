<?php require __DIR__ . '/../layout/header.php'; ?>
<h2 class="mb-4"><i class="bi bi-speedometer2"></i> Panel de Administración</h2>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-vet text-bg-primary"><div class="card-body">
        <div class="small">Usuarios</div><div class="fs-2 fw-bold"><?= $datos['total_usuarios'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card card-vet text-bg-success"><div class="card-body">
        <div class="small">Propietarios</div><div class="fs-2 fw-bold"><?= $datos['total_propietarios'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card card-vet text-bg-info"><div class="card-body">
        <div class="small">Mascotas</div><div class="fs-2 fw-bold"><?= $datos['total_mascotas'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card card-vet text-bg-warning"><div class="card-body">
        <div class="small">Productos / Stock bajo</div><div class="fs-2 fw-bold"><?= $datos['total_productos'] ?> / <?= count($datos['stock_bajo']) ?></div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card card-vet"><div class="card-header fw-bold"><i class="bi bi-calendar-event"></i> Próximas citas</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>Fecha</th><th>Mascota</th><th>Veterinario</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($datos['citas_proximas'] as $c): ?>
                    <tr><td><?= e($c['fecha']) ?> <?= substr($c['hora'],0,5) ?></td><td><?= e($c['mascota_nombre']) ?></td>
                        <td><?= e($c['vet_nombre']) ?></td><td><span class="badge bg-<?= badgeEstadoCita($c['estado']) ?>"><?= e($c['estado']) ?></span></td></tr>
                <?php endforeach; ?>
                <?php if (empty($datos['citas_proximas'])): ?><tr><td colspan="4" class="text-muted text-center py-3">Sin citas próximas</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card card-vet"><div class="card-header fw-bold text-danger"><i class="bi bi-exclamation-triangle"></i> Productos con stock bajo</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>Producto</th><th>Disponible</th><th>Mínimo</th></tr></thead>
                <tbody>
                <?php foreach ($datos['stock_bajo'] as $p): ?>
                    <tr class="table-danger"><td><?= e($p['nombre']) ?></td><td><?= $p['cantidad_disponible'] ?></td><td><?= $p['stock_minimo'] ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($datos['stock_bajo'])): ?><tr><td colspan="3" class="text-muted text-center py-3">Sin alertas de stock</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div></div>
    </div>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
