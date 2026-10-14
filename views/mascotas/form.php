<?php require __DIR__ . '/../layout/header.php'; ?>
<h2><i class="bi bi-heart-pulse"></i> <?= $mascota ? 'Editar mascota' : 'Nueva mascota' ?></h2>
<div class="card card-vet"><div class="card-body">
<form method="POST" action="index.php?route=mascotas.guardar">
    <?php if ($mascota): ?><input type="hidden" name="id" value="<?= $mascota['id'] ?>"><?php endif; ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>" value="<?= e($mascota['nombre'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Propietario</label>
            <select name="propietario_id" class="form-select <?= isset($errores['propietario_id']) ? 'is-invalid' : '' ?>" required>
                <option value="">Seleccione...</option>
                <?php foreach ($propietarios as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (isset($mascota['propietario_id']) && $mascota['propietario_id']==$p['id']) ? 'selected' : '' ?>><?= e($p['nombre'].' '.$p['apellido'].' - '.$p['documento']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errores['propietario_id'])): ?><div class="invalid-feedback"><?= e($errores['propietario_id']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label">Especie</label>
            <input type="text" name="especie" class="form-control <?= isset($errores['especie']) ? 'is-invalid' : '' ?>" value="<?= e($mascota['especie'] ?? '') ?>" required placeholder="Perro, gato...">
        </div>
        <div class="col-md-4">
            <label class="form-label">Raza</label>
            <input type="text" name="raza" class="form-control" value="<?= e($mascota['raza'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Sexo</label>
            <select name="sexo" class="form-select" required>
                <option value="macho" <?= (($mascota['sexo'] ?? '')==='macho')?'selected':'' ?>>Macho</option>
                <option value="hembra" <?= (($mascota['sexo'] ?? '')==='hembra')?'selected':'' ?>>Hembra</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Fecha de nacimiento</label>
            <input type="date" name="fecha_nacimiento" class="form-control <?= isset($errores['fecha_nacimiento']) ? 'is-invalid' : '' ?>" value="<?= e($mascota['fecha_nacimiento'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Peso actual (kg)</label>
            <input type="number" step="0.01" name="peso_actual" class="form-control <?= isset($errores['peso_actual']) ? 'is-invalid' : '' ?>" value="<?= e($mascota['peso_actual'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Color</label>
            <input type="text" name="color" class="form-control" value="<?= e($mascota['color'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Observaciones</label>
            <textarea name="observaciones" class="form-control" rows="2"><?= e($mascota['observaciones'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar</button>
        <a href="index.php?route=mascotas" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div></div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
