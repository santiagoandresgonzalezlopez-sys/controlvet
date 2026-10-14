-- Datos de prueba (idénticos a los del monolito v1.0.0).
-- Contraseñas de demostración: Admin123 / Recep123 / Vet123 / Cliente123.
-- Solo para entornos locales y de CI; cambiarlas en cualquier otro entorno.
INSERT IGNORE INTO roles (id, nombre) VALUES
 (1, 'administrador'),
 (2, 'recepcionista'),
 (3, 'veterinario'),
 (4, 'cliente');

INSERT IGNORE INTO usuarios (id, nombre, apellido, email, password_hash, rol_id, propietario_id) VALUES
 (1, 'Ana', 'Administradora', 'admin@controlvet.com', '$2y$10$9oXTK3pgbOdymzt5HNeBueQFDTmBbHUD6HgS/wVeoubW7ZbxHctUG', 1, NULL),
 (2, 'Paula', 'Recepción', 'recepcion@controlvet.com', '$2y$10$gZBaq3qAe3KI2oCO.5mSAOclw7X9mQlXZgb7aq5LENnthwgcXwVjG', 2, NULL),
 (3, 'Dr. Jorge', 'Martínez', 'veterinario@controlvet.com', '$2y$10$RNLTnWpgnxLg6Ynh7IrDlOOLNg7CPlFfRvOim9hcPDqVxrzF8Av36', 3, NULL),
 (4, 'Carlos', 'Ramírez', 'cliente@controlvet.com', '$2y$10$GC1XoMf69joG.NBVSxsSRuI73NhYyJdX0UCjzTUDwZKMCkY/Fu6ye', 4, 1);
