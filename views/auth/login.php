<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión · ControlVet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-bg d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow-lg border-0" style="width: 100%; max-width: 400px;">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <i class="bi bi-heart-pulse-fill display-4 text-success"></i>
                <h3 class="fw-bold mt-2">ControlVet</h3>
                <p class="text-muted small">Sistema de administración veterinaria</p>
            </div>

            <?php if (!empty($errores['general'])): ?>
                <div class="alert alert-danger py-2"><?= e($errores['general']) ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php?route=login.procesar">
                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" name="email" class="form-control <?= isset($errores['email']) ? 'is-invalid' : '' ?>" required>
                    <?php if (isset($errores['email'])): ?><div class="invalid-feedback"><?= e($errores['email']) ?></div><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control <?= isset($errores['password']) ? 'is-invalid' : '' ?>" required>
                    <?php if (isset($errores['password'])): ?><div class="invalid-feedback"><?= e($errores['password']) ?></div><?php endif; ?>
                </div>
                <button type="submit" class="btn btn-success w-100"><i class="bi bi-box-arrow-in-right"></i> Ingresar</button>
            </form>

            <hr>
            <p class="small text-muted mb-1">Credenciales de prueba:</p>
            <ul class="small text-muted mb-0">
                <li>Admin: admin@controlvet.com / Admin123</li>
                <li>Recepción: recepcion@controlvet.com / Recep123</li>
                <li>Veterinario: veterinario@controlvet.com / Vet123</li>
                <li>Cliente: cliente@controlvet.com / Cliente123</li>
            </ul>
        </div>
    </div>
</body>
</html>
