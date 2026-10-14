-- Datos de prueba (idénticos a los del monolito v1.0.0).
INSERT IGNORE INTO productos (id, nombre, categoria, tipo, descripcion, cantidad_disponible, stock_minimo, precio_referencia) VALUES
 (1, 'Vacuna polivalente canina', 'Biológico', 'Vacuna', 'Vacuna 5 en 1 para perros', 40, 10, 35000),
 (2, 'Antiparasitario oral gato', 'Medicamento', 'Antiparasitario', 'Tableta antiparasitaria felina', 8, 10, 18000),
 (3, 'Alimento premium adulto 15kg', 'Alimento', 'Concentrado', 'Alimento balanceado para perro adulto', 15, 5, 120000),
 (4, 'Jeringas 5ml', 'Insumo', 'Material médico', 'Caja x100 unidades', 3, 5, 25000),
 (5, 'Suero fisiológico 500ml', 'Insumo', 'Solución', 'Solución salina estéril', 20, 8, 9000);
