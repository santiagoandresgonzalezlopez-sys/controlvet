<?php require __DIR__ . '/../layout/header.php'; ?>
<h2><i class="bi bi-person-plus"></i> <?= $propietario ? 'Editar propietario' : 'Nuevo propietario' ?></h2>
<div class="card card-vet"><div class="card-body">
<form method="POST" action="index.php?route=propietarios.guardar">
    <?php if ($propietario): ?><input type="hidden" name="id" value="<?= $propietario['id'] ?>"><?php endif; ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>" value="<?= e($propietario['nombre'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Apellido</label>
            <input type="text" name="apellido" class="form-control <?= isset($errores['apellido']) ? 'is-invalid' : '' ?>" value="<?= e($propietario['apellido'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Documento</label>
            <input type="text" name="documento" class="form-control <?= isset($errores['documento']) ? 'is-invalid' : '' ?>" value="<?= e($propietario['documento'] ?? '') ?>" required>
            <?php if (isset($errores['documento'])): ?><div class="invalid-feedback"><?= e($errores['documento']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label">Teléfono</label>
            <input type="text" name="telefono" class="form-control <?= isset($errores['telefono']) ? 'is-invalid' : '' ?>" value="<?= e($propietario['telefono'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Correo electrónico</label>
            <input type="email" name="email" class="form-control <?= isset($errores['email']) ? 'is-invalid' : '' ?>" value="<?= e($propietario['email'] ?? '') ?>">
            <?php if (isset($errores['email'])): ?><div class="invalid-feedback"><?= e($errores['email']) ?></div><?php endif; ?>
        </div>
        <div class="col-12">
            <label class="form-label">Dirección</label>
            <input type="text" name="direccion" class="form-control" value="<?= e($propietario['direccion'] ?? '') ?>">
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar</button>
        <a href="index.php?route=propietarios" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div></div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
