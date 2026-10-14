<?php $usuario = Auth::user(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ControlVet · Clínica Veterinaria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-vet px-3">
    <a class="navbar-brand fw-bold" href="index.php?route=dashboard">
        <i class="bi bi-heart-pulse-fill"></i> ControlVet
    </a>
    <?php if ($usuario): ?>
    <div class="d-flex align-items-center ms-auto text-white">
        <span class="me-3 d-none d-md-inline">
            <i class="bi bi-person-circle"></i> <?= e($usuario['nombre']) ?>
            <span class="badge bg-light text-dark ms-1"><?= e(ucfirst($usuario['rol'])) ?></span>
        </span>
        <a href="index.php?route=logout" class="btn btn-sm btn-outline-light"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
    </div>
    <?php endif; ?>
</nav>
<div class="d-flex">
<?php if ($usuario): require __DIR__ . '/sidebar.php'; endif; ?>
<main class="flex-grow-1 p-4">
<?php if ($f = flash()): ?>
    <div class="alert alert-<?= e($f['tipo']) ?> alert-dismissible fade show" role="alert">
        <?= e($f['mensaje']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
