<?php require __DIR__ . '/../layout/header.php'; ?>
<h2><i class="bi bi-box-seam"></i> <?= $producto ? 'Editar producto' : 'Nuevo producto' ?></h2>
<div class="card card-vet"><div class="card-body">
<form method="POST" action="index.php?route=inventario.guardar">
    <?= csrf_field() ?>
    <?php if ($producto): ?><input type="hidden" name="id" value="<?= $producto['id'] ?>"><?php endif; ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>" value="<?= e($producto['nombre'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Categoría</label>
            <input type="text" name="categoria" class="form-control <?= isset($errores['categoria']) ? 'is-invalid' : '' ?>" value="<?= e($producto['categoria'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <input type="text" name="tipo" class="form-control" value="<?= e($producto['tipo'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" class="form-control" rows="2"><?= e($producto['descripcion'] ?? '') ?></textarea>
        </div>
        <?php if (!$producto): ?>
        <div class="col-md-4">
            <label class="form-label">Cantidad disponible inicial</label>
            <input type="number" name="cantidad_disponible" class="form-control <?= isset($errores['cantidad_disponible']) ? 'is-invalid' : '' ?>" required>
        </div>
        <?php endif; ?>
        <div class="col-md-4">
            <label class="form-label">Stock mínimo</label>
            <input type="number" name="stock_minimo" class="form-control <?= isset($errores['stock_minimo']) ? 'is-invalid' : '' ?>" value="<?= e($producto['stock_minimo'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Precio de referencia</label>
            <input type="number" step="0.01" name="precio_referencia" class="form-control <?= isset($errores['precio_referencia']) ? 'is-invalid' : '' ?>" value="<?= e($producto['precio_referencia'] ?? '') ?>">
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar</button>
        <a href="index.php?route=inventario" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div></div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
