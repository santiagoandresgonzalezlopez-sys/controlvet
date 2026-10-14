<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="bi bi-people"></i> Usuarios del sistema</h2>
    <a href="index.php?route=usuarios.crear" class="btn btn-success"><i class="bi bi-plus-circle"></i> Nuevo usuario</a>
</div>
<div class="card card-vet"><div class="card-body p-0">
<table class="table table-hover mb-0">
<thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach ($usuarios as $u): ?>
<tr>
    <td><?= e($u['nombre'].' '.$u['apellido']) ?></td>
    <td><?= e($u['email']) ?></td>
    <td><span class="badge bg-secondary"><?= e(ucfirst($u['rol_nombre'])) ?></span></td>
    <td><span class="badge bg-<?= $u['estado']==='activo' ? 'success' : 'danger' ?>"><?= e(ucfirst($u['estado'])) ?></span></td>
    <td>
        <a href="index.php?route=usuarios.editar&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
        <?php if ($u['estado'] === 'activo'): ?>
            <a href="index.php?route=usuarios.desactivar&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Desactivar este usuario?')"><i class="bi bi-slash-circle"></i></a>
        <?php else: ?>
            <a href="index.php?route=usuarios.activar&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('¿Activar este usuario?')"><i class="bi bi-check-circle"></i></a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
