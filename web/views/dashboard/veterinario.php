<?php require __DIR__ . '/../layout/header.php'; ?>
<h2 class="mb-4"><i class="bi bi-speedometer2"></i> Panel del Veterinario</h2>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card card-vet text-bg-primary"><div class="card-body">
        <div class="small">Citas de hoy</div><div class="fs-2 fw-bold"><?= count($datos['citas_hoy']) ?></div></div></div></div>
    <div class="col-md-4"><div class="card card-vet text-bg-info"><div class="card-body">
        <div class="small">Próximas consultas</div><div class="fs-2 fw-bold"><?= count($datos['citas_proximas']) ?></div></div></div></div>
</div>
<div class="row g-3">
<div class="col-md-6"><div class="card card-vet"><div class="card-header fw-bold">Agenda de hoy</div>
<div class="card-body p-0"><table class="table table-hover mb-0">
<thead><tr><th>Hora</th><th>Mascota</th><th>Motivo</th></tr></thead><tbody>
<?php foreach ($datos['citas_hoy'] as $c): ?>
<tr><td><?= substr($c['hora'],0,5) ?></td><td><?= e($c['mascota_nombre']) ?></td><td><?= e($c['motivo']) ?></td></tr>
<?php endforeach; ?>
<?php if (empty($datos['citas_hoy'])): ?><tr><td colspan="3" class="text-muted text-center py-3">Sin citas hoy</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<div class="col-md-6"><div class="card card-vet"><div class="card-header fw-bold">Pacientes recientes</div>
<div class="card-body p-0"><table class="table table-hover mb-0">
<thead><tr><th>Fecha</th><th>Mascota</th><th>Diagnóstico</th></tr></thead><tbody>
<?php foreach ($datos['pacientes_recientes'] as $h): ?>
<tr><td><?= e(date('Y-m-d', strtotime($h['fecha']))) ?></td><td><?= e($h['mascota_nombre']) ?></td><td><?= e($h['diagnostico']) ?></td></tr>
<?php endforeach; ?>
<?php if (empty($datos['pacientes_recientes'])): ?><tr><td colspan="3" class="text-muted text-center py-3">Sin registros</td></tr><?php endif; ?>
</tbody></table></div></div></div>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
