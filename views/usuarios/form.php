<?php require __DIR__ . '/../layout/header.php'; ?>
<h2><i class="bi bi-person-plus"></i> <?= $usuario ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
<div class="card card-vet"><div class="card-body">
<form method="POST" action="index.php?route=usuarios.guardar">
    <?php if ($usuario): ?><input type="hidden" name="id" value="<?= $usuario['id'] ?>"><?php endif; ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>" value="<?= e($usuario['nombre'] ?? '') ?>" required>
            <?php if (isset($errores['nombre'])): ?><div class="invalid-feedback"><?= e($errores['nombre']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label">Apellido</label>
            <input type="text" name="apellido" class="form-control <?= isset($errores['apellido']) ? 'is-invalid' : '' ?>" value="<?= e($usuario['apellido'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Correo electrónico</label>
            <input type="email" name="email" class="form-control <?= isset($errores['email']) ? 'is-invalid' : '' ?>" value="<?= e($usuario['email'] ?? '') ?>" required>
            <?php if (isset($errores['email'])): ?><div class="invalid-feedback"><?= e($errores['email']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label">Rol</label>
            <select name="rol_id" class="form-select" required>
                <option value="">Seleccione...</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= (isset($usuario['rol_id']) && $usuario['rol_id']==$r['id']) ? 'selected' : '' ?>><?= e(ucfirst($r['nombre'])) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?= $usuario ? 'Nueva contraseña (opcional)' : 'Contraseña' ?></label>
            <input type="password" name="password" class="form-control <?= isset($errores['password']) ? 'is-invalid' : '' ?>">
            <?php if (isset($errores['password'])): ?><div class="invalid-feedback"><?= e($errores['password']) ?></div><?php endif; ?>
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar</button>
        <a href="index.php?route=usuarios" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div></div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
