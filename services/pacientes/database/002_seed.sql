-- Datos de prueba (idénticos a los del monolito v1.0.0).
INSERT IGNORE INTO propietarios (id, nombre, apellido, documento, telefono, email, direccion) VALUES
 (1, 'Carlos', 'Ramírez', '1098765432', '3001234567', 'carlos.ramirez@correo.com', 'Cra 10 # 20-30, Bucaramanga'),
 (2, 'María', 'Gómez', '1087654321', '3009876543', 'maria.gomez@correo.com', 'Cll 45 # 12-08, Bucaramanga'),
 (3, 'Luis', 'Torres', '1076543210', '3012345678', 'luis.torres@correo.com', 'Cra 27 # 33-10, Floridablanca');

INSERT IGNORE INTO mascotas (id, nombre, especie, raza, sexo, fecha_nacimiento, peso_actual, color, propietario_id, observaciones) VALUES
 (1, 'Rocky', 'Perro', 'Labrador', 'macho', '2021-03-10', 28.50, 'Dorado', 1, 'Alergia leve a pollo'),
 (2, 'Michi', 'Gato', 'Criollo', 'hembra', '2022-06-01', 4.20, 'Negro', 1, NULL),
 (3, 'Luna', 'Perro', 'Poodle', 'hembra', '2020-11-20', 8.10, 'Blanco', 2, NULL),
 (4, 'Max', 'Perro', 'Bóxer', 'macho', '2019-05-15', 25.00, 'Atigrado', 3, 'Displasia de cadera controlada');
