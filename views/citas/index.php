<?php require __DIR__ . '/../layout/header.php'; $rol = Auth::user()['rol']; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="bi bi-calendar-event"></i> Citas</h2>
    <?php if (in_array($rol, ['administrador','recepcionista'])): ?>
    <a href="index.php?route=citas.crear" class="btn btn-success"><i class="bi bi-plus-circle"></i> Nueva cita</a>
    <?php endif; ?>
</div>

<form method="GET" action="index.php" class="row g-2 mb-3">
    <input type="hidden" name="route" value="citas">
    <div class="col-md-3"><input type="date" name="fecha" class="form-control" value="<?= e($_GET['fecha'] ?? '') ?>"></div>
    <?php if (in_array($rol, ['administrador','recepcionista'])): ?>
    <div class="col-md-3">
        <select name="veterinario_id" class="form-select">
            <option value="">Todos los veterinarios</option>
            <?php foreach ($veterinarios as $v): ?>
                <option value="<?= $v['id'] ?>" <?= (($_GET['veterinario_id'] ?? '')==$v['id'])?'selected':'' ?>><?= e($v['nombre'].' '.$v['apellido']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="col-md-3">
        <select name="estado" class="form-select">
            <option value="">Todos los estados</option>
            <?php foreach (['programada','confirmada','atendida','cancelada'] as $es): ?>
                <option value="<?= $es ?>" <?= (($_GET['estado'] ?? '')===$es)?'selected':'' ?>><?= ucfirst($es) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-funnel"></i> Filtrar</button></div>
</form>

<div class="card card-vet"><div class="card-body p-0">
<table class="table table-hover mb-0">
<thead><tr><th>Fecha</th><th>Hora</th><th>Mascota</th><th>Propietario</th><th>Veterinario</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach ($citas as $c): ?>
<tr>
    <td><?= e($c['fecha']) ?></td>
    <td><?= substr($c['hora'],0,5) ?></td>
    <td><?= e($c['mascota_nombre']) ?></td>
    <td><?= e($c['propietario_nombre'].' '.$c['propietario_apellido']) ?></td>
    <td><?= e($c['vet_nombre'].' '.$c['vet_apellido']) ?></td>
    <td><span class="badge bg-<?= badgeEstadoCita($c['estado']) ?>"><?= e(ucfirst($c['estado'])) ?></span></td>
    <td class="text-nowrap">
        <a href="index.php?route=citas.pdf&id=<?= $c['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Comprobante"><i class="bi bi-file-earmark-pdf"></i></a>
        <?php if (in_array($rol, ['administrador','recepcionista'])): ?>
            <a href="index.php?route=citas.editar&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <a href="index.php?route=citas.estado&id=<?= $c['id'] ?>&estado=confirmada" class="btn btn-sm btn-outline-info" title="Confirmar"><i class="bi bi-check2"></i></a>
            <a href="index.php?route=citas.estado&id=<?= $c['id'] ?>&estado=cancelada" class="btn btn-sm btn-outline-danger" title="Cancelar" onclick="return confirm('¿Cancelar esta cita?')"><i class="bi bi-x-circle"></i></a>
        <?php endif; ?>
        <?php if ($rol === 'veterinario'): ?>
            <a href="index.php?route=citas.estado&id=<?= $c['id'] ?>&estado=atendida" class="btn btn-sm btn-outline-success" title="Marcar atendida"><i class="bi bi-clipboard2-pulse"></i></a>
            <a href="index.php?route=historias.crear&mascota_id=<?= $c['mascota_id'] ?>" class="btn btn-sm btn-outline-dark" title="Registrar consulta"><i class="bi bi-journal-medical"></i></a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($citas)): ?><tr><td colspan="7" class="text-muted text-center py-3">Sin citas registradas</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
