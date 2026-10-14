-- Datos de prueba (idénticos a los del monolito v1.0.0, más la copia del propietario).
INSERT IGNORE INTO historias_clinicas (id, mascota_id, propietario_id, veterinario_id, cita_id, motivo_consulta, peso, sintomas, diagnostico, tratamiento, observaciones) VALUES
 (1, 4, 3, 3, 4, 'Chequeo displasia', 25.00, 'Leve cojera en tren posterior', 'Displasia de cadera grado I, estable', 'Condroprotector y control de peso', 'Reevaluar en 6 meses'),
 (2, 1, 1, 3, NULL, 'Vacunación anual previa', 27.80, 'Ninguno', 'Paciente sano', 'Refuerzo vacuna polivalente', 'Próxima dosis en 12 meses');

INSERT IGNORE INTO formulas_medicas (id, historia_id, medicamento, dosis, frecuencia, duracion, indicaciones) VALUES
 (1, 1, 'Condroitina + Glucosamina', '1 tableta', 'Cada 24 horas', '30 días', 'Administrar con alimento. Controlar peso semanalmente.');
