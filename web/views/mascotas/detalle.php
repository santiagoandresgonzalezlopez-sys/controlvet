<?php require __DIR__ . '/../layout/header.php'; $rol = Auth::user()['rol']; ?>
<h2><i class="bi bi-heart-pulse"></i> <?= e($mascota['nombre']) ?> <small class="text-muted fs-6">(<?= e($mascota['especie']) ?> · <?= e($mascota['raza']) ?>)</small></h2>
<div class="row g-3 mb-3">
    <div class="col-md-6"><div class="card card-vet"><div class="card-body">
        <p><strong>Propietario:</strong> <?= e($mascota['propietario_nombre'].' '.$mascota['propietario_apellido']) ?></p>
        <p><strong>Sexo:</strong> <?= e(ucfirst($mascota['sexo'])) ?> &nbsp; <strong>Peso actual:</strong> <?= e($mascota['peso_actual']) ?> kg</p>
        <p><strong>Fecha de nacimiento:</strong> <?= e($mascota['fecha_nacimiento']) ?> &nbsp; <strong>Color:</strong> <?= e($mascota['color']) ?></p>
        <p class="mb-0"><strong>Observaciones:</strong> <?= e($mascota['observaciones']) ?></p>
    </div></div></div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h4 class="mb-0">Historial clínico</h4>
    <?php if ($rol === 'veterinario'): ?>
    <a href="index.php?route=historias.crear&mascota_id=<?= $mascota['id'] ?>" class="btn btn-sm btn-success"><i class="bi bi-plus-circle"></i> Registrar consulta</a>
    <?php endif; ?>
</div>
<div class="card card-vet"><div class="card-body p-0"><table class="table table-hover mb-0">
<thead><tr><th>Fecha</th><th>Veterinario</th><th>Motivo</th><th>Diagnóstico</th><th></th></tr></thead><tbody>
<?php foreach ($historial as $h): ?>
<tr>
    <td><?= e(date('Y-m-d', strtotime($h['fecha']))) ?></td>
    <td><?= e($h['vet_nombre'].' '.$h['vet_apellido']) ?></td>
    <td><?= e($h['motivo_consulta']) ?></td>
    <td><?= e($h['diagnostico']) ?></td>
    <td><a href="index.php?route=historias.detalle&id=<?= $h['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a></td>
</tr>
<?php endforeach; ?>
<?php if (empty($historial)): ?><tr><td colspan="5" class="text-muted text-center py-3">Sin historial clínico registrado</td></tr><?php endif; ?>
</tbody></table></div></div>
<a href="index.php?route=mascotas" class="btn btn-outline-secondary mt-3">Volver</a>
<?php require __DIR__ . '/../layout/footer.php'; ?>
