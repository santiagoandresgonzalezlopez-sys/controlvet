<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="bi bi-person-vcard"></i> Propietarios</h2>
    <a href="index.php?route=propietarios.crear" class="btn btn-success"><i class="bi bi-plus-circle"></i> Nuevo propietario</a>
</div>
<form method="GET" action="index.php" class="mb-3 d-flex gap-2">
    <input type="hidden" name="route" value="propietarios">
    <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre, apellido o documento" value="<?= e($_GET['buscar'] ?? '') ?>">
    <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
</form>
<div class="card card-vet"><div class="card-body p-0">
<table class="table table-hover mb-0">
<thead><tr><th>Nombre</th><th>Documento</th><th>Teléfono</th><th>Correo</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach ($propietarios as $p): ?>
<tr>
    <td><?= e($p['nombre'].' '.$p['apellido']) ?></td>
    <td><?= e($p['documento']) ?></td>
    <td><?= e($p['telefono']) ?></td>
    <td><?= e($p['email']) ?></td>
    <td>
        <a href="index.php?route=propietarios.detalle&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
        <a href="index.php?route=propietarios.editar&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
        <a href="index.php?route=propietarios.desactivar&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Desactivar este propietario?')"><i class="bi bi-slash-circle"></i></a>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($propietarios)): ?><tr><td colspan="5" class="text-muted text-center py-3">Sin resultados</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
