<?php require __DIR__ . '/../layout/header.php'; $rol = Auth::user()['rol']; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="bi bi-heart-pulse"></i> Mascotas</h2>
    <?php if (in_array($rol, ['administrador','recepcionista'])): ?>
    <a href="index.php?route=mascotas.crear" class="btn btn-success"><i class="bi bi-plus-circle"></i> Nueva mascota</a>
    <?php endif; ?>
</div>
<form method="GET" action="index.php" class="mb-3 d-flex gap-2">
    <input type="hidden" name="route" value="mascotas">
    <input type="text" name="buscar" class="form-control" placeholder="Buscar por mascota o propietario" value="<?= e($_GET['buscar'] ?? '') ?>">
    <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
</form>
<div class="card card-vet"><div class="card-body p-0">
<table class="table table-hover mb-0">
<thead><tr><th>Nombre</th><th>Especie</th><th>Raza</th><th>Propietario</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach ($mascotas as $m): ?>
<tr>
    <td><?= e($m['nombre']) ?></td>
    <td><?= e($m['especie']) ?></td>
    <td><?= e($m['raza']) ?></td>
    <td><?= e($m['propietario_nombre'].' '.$m['propietario_apellido']) ?></td>
    <td>
        <a href="index.php?route=mascotas.detalle&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
        <?php if (in_array($rol, ['administrador','recepcionista'])): ?>
        <a href="index.php?route=mascotas.editar&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($mascotas)): ?><tr><td colspan="5" class="text-muted text-center py-3">Sin resultados</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
