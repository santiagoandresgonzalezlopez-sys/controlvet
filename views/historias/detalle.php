<?php require __DIR__ . '/../layout/header.php'; $rol = Auth::user()['rol']; ?>
<h2><i class="bi bi-journal-medical"></i> Consulta del <?= e(date('Y-m-d', strtotime($historia['fecha']))) ?></h2>
<div class="card card-vet mb-3"><div class="card-body">
    <p><strong>Paciente:</strong> <?= e($historia['mascota_nombre']) ?> (<?= e($historia['especie']) ?>)</p>
    <p><strong>Propietario:</strong> <?= e($historia['propietario_nombre'].' '.$historia['propietario_apellido']) ?></p>
    <p><strong>Veterinario:</strong> Dr(a). <?= e($historia['vet_nombre'].' '.$historia['vet_apellido']) ?></p>
    <p><strong>Motivo:</strong> <?= e($historia['motivo_consulta']) ?> &nbsp; <strong>Peso:</strong> <?= e($historia['peso']) ?> kg</p>
    <p><strong>Síntomas:</strong> <?= e($historia['sintomas']) ?></p>
    <p><strong>Diagnóstico:</strong> <?= e($historia['diagnostico']) ?></p>
    <p><strong>Tratamiento:</strong> <?= e($historia['tratamiento']) ?></p>
    <p class="mb-0"><strong>Observaciones:</strong> <?= e($historia['observaciones']) ?></p>
</div></div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h4 class="mb-0">Fórmulas médicas</h4>
    <div>
        <a href="index.php?route=historias.pdf&id=<?= $historia['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf"></i> Resumen PDF</a>
        <?php if ($rol === 'veterinario'): ?>
        <a href="index.php?route=historias.formula.crear&historia_id=<?= $historia['id'] ?>" class="btn btn-sm btn-success"><i class="bi bi-plus-circle"></i> Nueva fórmula</a>
        <?php endif; ?>
    </div>
</div>
<div class="card card-vet"><div class="card-body p-0"><table class="table table-hover mb-0">
<thead><tr><th>Medicamento</th><th>Dosis</th><th>Frecuencia</th><th>Duración</th><th></th></tr></thead><tbody>
<?php foreach ($formulas as $f): ?>
<tr><td><?= e($f['medicamento']) ?></td><td><?= e($f['dosis']) ?></td><td><?= e($f['frecuencia']) ?></td><td><?= e($f['duracion']) ?></td>
<td><a href="index.php?route=historias.formula.pdf&id=<?= $f['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf"></i></a></td></tr>
<?php endforeach; ?>
<?php if (empty($formulas)): ?><tr><td colspan="5" class="text-muted text-center py-3">Sin fórmulas médicas registradas</td></tr><?php endif; ?>
</tbody></table></div></div>
<a href="index.php?route=mascotas.detalle&id=<?= $historia['mascota_id'] ?>" class="btn btn-outline-secondary mt-3">Volver al paciente</a>
<?php require __DIR__ . '/../layout/footer.php'; ?>
