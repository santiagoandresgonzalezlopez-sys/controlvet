<?php require __DIR__ . '/../layout/header.php'; ?>
<h2><i class="bi bi-calendar-plus"></i> <?= $cita ? 'Editar cita' : 'Nueva cita' ?></h2>
<div class="card card-vet"><div class="card-body">
<form method="POST" action="index.php?route=citas.guardar">
    <?php if ($cita): ?><input type="hidden" name="id" value="<?= $cita['id'] ?>"><?php endif; ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Paciente (mascota)</label>
            <select name="mascota_id" class="form-select <?= isset($errores['mascota_id']) ? 'is-invalid' : '' ?>" required>
                <option value="">Seleccione...</option>
                <?php foreach ($mascotas as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= (isset($cita['mascota_id']) && $cita['mascota_id']==$m['id']) ? 'selected' : '' ?>>
                        <?= e($m['nombre'].' - '.$m['propietario_nombre'].' '.$m['propietario_apellido']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errores['mascota_id'])): ?><div class="invalid-feedback"><?= e($errores['mascota_id']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label">Veterinario</label>
            <select name="veterinario_id" class="form-select <?= isset($errores['veterinario_id']) ? 'is-invalid' : '' ?>" required>
                <option value="">Seleccione...</option>
                <?php foreach ($veterinarios as $v): ?>
                    <option value="<?= $v['id'] ?>" <?= (isset($cita['veterinario_id']) && $cita['veterinario_id']==$v['id']) ? 'selected' : '' ?>><?= e($v['nombre'].' '.$v['apellido']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errores['veterinario_id'])): ?><div class="invalid-feedback"><?= e($errores['veterinario_id']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label">Fecha</label>
            <input type="date" name="fecha" class="form-control <?= isset($errores['fecha']) ? 'is-invalid' : '' ?>" value="<?= e($cita['fecha'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Hora</label>
            <input type="time" name="hora" class="form-control <?= isset($errores['hora']) ? 'is-invalid' : '' ?>" value="<?= e(substr($cita['hora'] ?? '',0,5)) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Motivo</label>
            <input type="text" name="motivo" class="form-control <?= isset($errores['motivo']) ? 'is-invalid' : '' ?>" value="<?= e($cita['motivo'] ?? '') ?>" required>
        </div>
        <div class="col-12">
            <label class="form-label">Observaciones</label>
            <textarea name="observaciones" class="form-control" rows="2"><?= e($cita['observaciones'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar</button>
        <a href="index.php?route=citas" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div></div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
