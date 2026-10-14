<?php require __DIR__ . '/../layout/header.php'; ?>
<h2><i class="bi bi-journal-medical"></i> Registrar consulta — <?= e($mascota['nombre']) ?></h2>
<div class="card card-vet"><div class="card-body">
<form method="POST" action="index.php?route=historias.guardar">
    <input type="hidden" name="mascota_id" value="<?= $mascota['id'] ?>">
    <?php if (!empty($_GET['cita_id'])): ?><input type="hidden" name="cita_id" value="<?= (int)$_GET['cita_id'] ?>"><?php endif; ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Motivo de consulta</label>
            <input type="text" name="motivo_consulta" class="form-control <?= isset($errores['motivo_consulta']) ? 'is-invalid' : '' ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Peso (kg)</label>
            <input type="number" step="0.01" name="peso" class="form-control <?= isset($errores['peso']) ? 'is-invalid' : '' ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Síntomas</label>
            <textarea name="sintomas" class="form-control" rows="2"></textarea>
        </div>
        <div class="col-12">
            <label class="form-label">Diagnóstico</label>
            <textarea name="diagnostico" class="form-control <?= isset($errores['diagnostico']) ? 'is-invalid' : '' ?>" rows="2" required></textarea>
        </div>
        <div class="col-12">
            <label class="form-label">Tratamiento</label>
            <textarea name="tratamiento" class="form-control" rows="2"></textarea>
        </div>
        <div class="col-12">
            <label class="form-label">Observaciones</label>
            <textarea name="observaciones" class="form-control" rows="2"></textarea>
        </div>
    </div>
    <div class="alert alert-warning mt-3 small"><i class="bi bi-info-circle"></i> Una vez guardada, esta consulta pasará a formar parte del historial clínico inmutable del paciente.</div>
    <div class="mt-2">
        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar consulta</button>
        <a href="index.php?route=mascotas.detalle&id=<?= $mascota['id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div></div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
