<?php require __DIR__ . '/../layout/header.php'; ?>
<h2><i class="bi bi-capsule"></i> Nueva fórmula médica</h2>
<div class="card card-vet"><div class="card-body">
<form method="POST" action="index.php?route=historias.formula.guardar">
    <?= csrf_field() ?>
    <input type="hidden" name="historia_id" value="<?= $historia['id'] ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Medicamento</label>
            <input type="text" name="medicamento" class="form-control <?= isset($errores['medicamento']) ? 'is-invalid' : '' ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Dosis</label>
            <input type="text" name="dosis" class="form-control <?= isset($errores['dosis']) ? 'is-invalid' : '' ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Frecuencia</label>
            <input type="text" name="frecuencia" class="form-control <?= isset($errores['frecuencia']) ? 'is-invalid' : '' ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Duración</label>
            <input type="text" name="duracion" class="form-control <?= isset($errores['duracion']) ? 'is-invalid' : '' ?>" required>
        </div>
        <div class="col-12">
            <label class="form-label">Indicaciones</label>
            <textarea name="indicaciones" class="form-control" rows="2"></textarea>
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar fórmula</button>
        <a href="index.php?route=historias.detalle&id=<?= $historia['id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div></div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
