<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="bi bi-box-seam"></i> Inventario</h2>
    <a href="index.php?route=inventario.crear" class="btn btn-success"><i class="bi bi-plus-circle"></i> Nuevo producto</a>
</div>
<form method="GET" action="index.php" class="mb-3 d-flex gap-2">
    <input type="hidden" name="route" value="inventario">
    <input type="text" name="buscar" class="form-control" placeholder="Buscar producto o categoría" value="<?= e($_GET['buscar'] ?? '') ?>">
    <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
</form>
<div class="card card-vet"><div class="card-body p-0">
<table class="table table-hover mb-0" id="tablaInventario">
<thead><tr><th>Producto</th><th>Categoría</th><th>Disponible</th><th>Mínimo</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach ($productos as $p): $bajo = $p['cantidad_disponible'] <= $p['stock_minimo']; ?>
<tr class="<?= $bajo ? 'table-danger' : '' ?>" data-id="<?= $p['id'] ?>">
    <td><?= e($p['nombre']) ?></td>
    <td><?= e($p['categoria']) ?></td>
    <td class="cantidad-disponible"><?= $p['cantidad_disponible'] ?></td>
    <td><?= $p['stock_minimo'] ?></td>
    <td><?= $p['precio_referencia'] !== null ? '$'.number_format($p['precio_referencia'],0,',','.') : '-' ?></td>
    <td><?= $bajo ? '<span class="badge bg-danger">Stock bajo</span>' : '<span class="badge bg-success">Normal</span>' ?></td>
    <td class="text-nowrap">
        <button class="btn btn-sm btn-outline-secondary btn-stock" data-id="<?= $p['id'] ?>" data-cantidad="<?= $p['cantidad_disponible'] ?>" title="Actualizar stock (vía API JWT)"><i class="bi bi-arrow-repeat"></i></button>
        <a href="index.php?route=inventario.editar&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
        <a href="index.php?route=inventario.desactivar&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Desactivar este producto?')"><i class="bi bi-slash-circle"></i></a>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($productos)): ?><tr><td colspan="7" class="text-muted text-center py-3">Sin productos registrados</td></tr><?php endif; ?>
</tbody></table></div></div>
<p class="small text-muted mt-2"><i class="bi bi-shield-lock"></i> La actualización rápida de stock usa el endpoint <code>api/stock.php</code>, autenticado mediante JWT (Bearer token), independiente de la sesión web.</p>
<?php require __DIR__ . '/../layout/footer.php'; ?>
