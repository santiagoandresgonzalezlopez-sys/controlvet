<?php require __DIR__ . '/../layout/header.php'; ?>
<h2><i class="bi bi-person-vcard"></i> <?= e($propietario['nombre'].' '.$propietario['apellido']) ?></h2>
<div class="row g-3 mb-3">
    <div class="col-md-6"><div class="card card-vet"><div class="card-body">
        <p><strong>Documento:</strong> <?= e($propietario['documento']) ?></p>
        <p><strong>Teléfono:</strong> <?= e($propietario['telefono']) ?></p>
        <p><strong>Correo:</strong> <?= e($propietario['email']) ?></p>
        <p class="mb-0"><strong>Dirección:</strong> <?= e($propietario['direccion']) ?></p>
    </div></div></div>
</div>
<div class="card card-vet"><div class="card-header fw-bold">Mascotas asociadas</div>
<div class="card-body p-0"><table class="table table-hover mb-0">
<thead><tr><th>Nombre</th><th>Especie</th><th>Raza</th><th></th></tr></thead><tbody>
<?php foreach ($mascotas as $m): ?>
<tr><td><?= e($m['nombre']) ?></td><td><?= e($m['especie']) ?></td><td><?= e($m['raza']) ?></td>
<td><a href="index.php?route=mascotas.detalle&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a></td></tr>
<?php endforeach; ?>
<?php if (empty($mascotas)): ?><tr><td colspan="4" class="text-muted text-center py-3">Sin mascotas registradas</td></tr><?php endif; ?>
</tbody></table></div></div>
<a href="index.php?route=propietarios" class="btn btn-outline-secondary mt-3">Volver</a>
<?php require __DIR__ . '/../layout/footer.php'; ?>
