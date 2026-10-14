<?php require __DIR__ . '/../layout/header.php'; ?>
<h2 class="mb-4"><i class="bi bi-speedometer2"></i> Mi Panel</h2>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card card-vet text-bg-success"><div class="card-body">
        <div class="small">Mis mascotas</div><div class="fs-2 fw-bold"><?= count($datos['mascotas']) ?></div></div></div></div>
    <div class="col-md-4"><div class="card card-vet text-bg-primary"><div class="card-body">
        <div class="small">Próximas citas</div><div class="fs-2 fw-bold"><?= count($datos['citas_proximas']) ?></div></div></div></div>
    <div class="col-md-4"><div class="card card-vet text-bg-info"><div class="card-body">
        <div class="small">Documentos disponibles</div><div class="fs-2 fw-bold"><?= count($datos['documentos']) ?></div></div></div></div>
</div>

<div class="card card-vet mb-3"><div class="card-header fw-bold">Mis mascotas</div>
<div class="card-body p-0"><table class="table table-hover mb-0">
<thead><tr><th>Nombre</th><th>Especie</th><th>Raza</th><th></th></tr></thead><tbody>
<?php foreach ($datos['mascotas'] as $m): ?>
<tr><td><?= e($m['nombre']) ?></td><td><?= e($m['especie']) ?></td><td><?= e($m['raza']) ?></td>
<td><a href="index.php?route=mascotas.detalle&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary">Ver historial</a></td></tr>
<?php endforeach; ?>
<?php if (empty($datos['mascotas'])): ?><tr><td colspan="4" class="text-muted text-center py-3">No tiene mascotas registradas</td></tr><?php endif; ?>
</tbody></table></div></div>

<div class="card card-vet"><div class="card-header fw-bold">Últimas consultas</div>
<div class="card-body p-0"><table class="table table-hover mb-0">
<thead><tr><th>Fecha</th><th>Mascota</th><th>Diagnóstico</th></tr></thead><tbody>
<?php foreach ($datos['historial'] as $h): ?>
<tr><td><?= e(date('Y-m-d', strtotime($h['fecha']))) ?></td><td><?= e($h['mascota_nombre']) ?></td><td><?= e($h['diagnostico']) ?></td></tr>
<?php endforeach; ?>
<?php if (empty($datos['historial'])): ?><tr><td colspan="3" class="text-muted text-center py-3">Sin consultas registradas</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
