<?php require __DIR__ . '/../layout/header.php'; ?>
<h2><i class="bi bi-file-earmark-pdf"></i> Documentos generados</h2>
<div class="card card-vet"><div class="card-body p-0">
<table class="table table-hover mb-0">
<thead><tr><th>Tipo</th><th>Archivo</th><th>Fecha de generación</th></tr></thead>
<tbody>
<?php foreach ($documentos as $d):
    $etiquetas = ['comprobante_cita' => 'Comprobante de cita', 'resumen_consulta' => 'Resumen de consulta', 'formula_medica' => 'Fórmula médica'];
    $ruta = match ($d['tipo']) {
        'comprobante_cita' => 'citas.pdf&id=' . $d['referencia_id'],
        'resumen_consulta' => 'historias.pdf&id=' . $d['referencia_id'],
        'formula_medica'   => 'historias.formula.pdf&id=' . $d['referencia_id'],
        default => '#',
    };
?>
<tr>
    <td><span class="badge bg-secondary"><?= e($etiquetas[$d['tipo']] ?? $d['tipo']) ?></span></td>
    <td><?= e($d['nombre_archivo']) ?></td>
    <td><?= e($d['fecha_generacion']) ?></td>
    <td><a href="index.php?route=<?= $ruta ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i> Descargar</a></td>
</tr>
<?php endforeach; ?>
<?php if (empty($documentos)): ?><tr><td colspan="4" class="text-muted text-center py-3">Sin documentos generados aún</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
