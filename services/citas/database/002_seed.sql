-- Datos de prueba (idénticos a los del monolito v1.0.0, más la copia del propietario).
INSERT IGNORE INTO citas (id, mascota_id, propietario_id, veterinario_id, fecha, hora, motivo, estado, creado_por) VALUES
 (1, 1, 1, 3, CURDATE(), '09:00:00', 'Control anual y vacunación', 'confirmada', 2),
 (2, 3, 2, 3, CURDATE(), '11:30:00', 'Revisión por cojera', 'programada', 2),
 (3, 2, 1, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '14:00:00', 'Esterilización - valoración previa', 'programada', 2),
 (4, 4, 3, 3, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '10:00:00', 'Chequeo displasia', 'atendida', 2);
