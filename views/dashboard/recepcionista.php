<?php require __DIR__ . '/../layout/header.php'; ?>
<h2 class="mb-4"><i class="bi bi-speedometer2"></i> Panel de Recepción</h2>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-vet text-bg-primary"><div class="card-body">
        <div class="small">Citas de hoy</div><div class="fs-2 fw-bold"><?= count($datos['citas_hoy']) ?></div></div></div></div>
    <div class="col-md-3"><div class="card card-vet text-bg-success"><div class="card-body">
        <div class="small">Propietarios</div><div class="fs-2 fw-bold"><?= $datos['total_propietarios'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card card-vet text-bg-info"><div class="card-body">
        <div class="small">Mascotas</div><div class="fs-2 fw-bold"><?= $datos['total_mascotas'] ?></div></div></div></div>
</div>
<div class="card card-vet"><div class="card-header fw-bold">Agenda de hoy</div>
<div class="card-body p-0">
<table class="table table-hover mb-0">
<thead><tr><th>Hora</th><th>Mascota</th><th>Propietario</th><th>Veterinario</th><th>Estado</th></tr></thead>
<tbody>
<?php foreach ($datos['citas_hoy'] as $c): ?>
<tr><td><?= substr($c['hora'],0,5) ?></td><td><?= e($c['mascota_nombre']) ?></td>
<td><?= e($c['propietario_nombre'].' '.$c['propietario_apellido']) ?></td><td><?= e($c['vet_nombre']) ?></td>
<td><span class="badge bg-<?= badgeEstadoCita($c['estado']) ?>"><?= e($c['estado']) ?></span></td></tr>
<?php endforeach; ?>
<?php if (empty($datos['citas_hoy'])): ?><tr><td colspan="5" class="text-muted text-center py-3">Sin citas para hoy</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
