-- =====================================================================
-- ControlVet - Script de creación de base de datos
-- Motor: MySQL / MariaDB (XAMPP)
-- Ejecutar en phpMyAdmin o: mysql -u root < controlvet.sql
-- =====================================================================

DROP DATABASE IF EXISTS controlvet;
CREATE DATABASE controlvet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE controlvet;

-- ---------------------------------------------------------------------
-- Tabla: roles
-- ---------------------------------------------------------------------
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO roles (id, nombre) VALUES
 (1, 'administrador'),
 (2, 'recepcionista'),
 (3, 'veterinario'),
 (4, 'cliente');

-- ---------------------------------------------------------------------
-- Tabla: propietarios
-- ---------------------------------------------------------------------
CREATE TABLE propietarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    apellido VARCHAR(80) NOT NULL,
    documento VARCHAR(30) NOT NULL UNIQUE,
    telefono VARCHAR(20) NOT NULL,
    email VARCHAR(120) NULL,
    direccion VARCHAR(180) NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    INDEX idx_propietarios_documento (documento),
    INDEX idx_propietarios_apellido (apellido)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabla: usuarios  (login del sistema; un usuario "cliente" se enlaza
-- opcionalmente a un registro de propietarios)
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    apellido VARCHAR(80) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    propietario_id INT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE RESTRICT,
    CONSTRAINT fk_usuarios_propietario FOREIGN KEY (propietario_id) REFERENCES propietarios(id) ON DELETE RESTRICT,
    INDEX idx_usuarios_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabla: mascotas
-- ---------------------------------------------------------------------
CREATE TABLE mascotas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    especie VARCHAR(40) NOT NULL,
    raza VARCHAR(60) NULL,
    sexo ENUM('macho','hembra') NOT NULL,
    fecha_nacimiento DATE NULL,
    peso_actual DECIMAL(6,2) NULL,
    color VARCHAR(40) NULL,
    propietario_id INT NOT NULL,
    observaciones TEXT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    CONSTRAINT fk_mascotas_propietario FOREIGN KEY (propietario_id) REFERENCES propietarios(id) ON DELETE RESTRICT,
    INDEX idx_mascotas_nombre (nombre)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabla: citas
-- ---------------------------------------------------------------------
CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mascota_id INT NOT NULL,
    veterinario_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    motivo VARCHAR(200) NOT NULL,
    estado ENUM('programada','confirmada','atendida','cancelada') NOT NULL DEFAULT 'programada',
    observaciones TEXT NULL,
    creado_por INT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_citas_mascota FOREIGN KEY (mascota_id) REFERENCES mascotas(id) ON DELETE RESTRICT,
    CONSTRAINT fk_citas_veterinario FOREIGN KEY (veterinario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    CONSTRAINT fk_citas_creador FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_citas_fecha (fecha),
    INDEX idx_citas_estado (estado)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabla: historias_clinicas (histórico inmutable de consultas)
-- ---------------------------------------------------------------------
CREATE TABLE historias_clinicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mascota_id INT NOT NULL,
    veterinario_id INT NOT NULL,
    cita_id INT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motivo_consulta VARCHAR(200) NOT NULL,
    peso DECIMAL(6,2) NULL,
    sintomas TEXT NULL,
    diagnostico TEXT NOT NULL,
    tratamiento TEXT NULL,
    observaciones TEXT NULL,
    CONSTRAINT fk_historias_mascota FOREIGN KEY (mascota_id) REFERENCES mascotas(id) ON DELETE RESTRICT,
    CONSTRAINT fk_historias_veterinario FOREIGN KEY (veterinario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    CONSTRAINT fk_historias_cita FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE SET NULL,
    INDEX idx_historias_mascota (mascota_id),
    INDEX idx_historias_fecha (fecha)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabla: formulas_medicas (generadas a partir de una historia clínica)
-- ---------------------------------------------------------------------
CREATE TABLE formulas_medicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    historia_id INT NOT NULL,
    medicamento VARCHAR(150) NOT NULL,
    dosis VARCHAR(80) NOT NULL,
    frecuencia VARCHAR(80) NOT NULL,
    duracion VARCHAR(80) NOT NULL,
    indicaciones TEXT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_formulas_historia FOREIGN KEY (historia_id) REFERENCES historias_clinicas(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabla: productos (inventario)
-- ---------------------------------------------------------------------
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    categoria VARCHAR(60) NOT NULL,
    tipo VARCHAR(60) NULL,
    descripcion TEXT NULL,
    cantidad_disponible INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 0,
    precio_referencia DECIMAL(10,2) NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    INDEX idx_productos_nombre (nombre),
    INDEX idx_productos_categoria (categoria)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabla: documentos (registro de PDF generados)
-- ---------------------------------------------------------------------
CREATE TABLE documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('comprobante_cita','resumen_consulta','formula_medica') NOT NULL,
    referencia_id INT NOT NULL,
    nombre_archivo VARCHAR(180) NOT NULL,
    generado_por INT NULL,
    fecha_generacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documentos_usuario FOREIGN KEY (generado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_documentos_tipo (tipo)
) ENGINE=InnoDB;

-- =====================================================================
-- DATOS DE PRUEBA
-- =====================================================================

-- Propietarios de prueba
INSERT INTO propietarios (nombre, apellido, documento, telefono, email, direccion) VALUES
 ('Carlos', 'Ramírez', '1098765432', '3001234567', 'carlos.ramirez@correo.com', 'Cra 10 # 20-30, Bucaramanga'),
 ('María', 'Gómez', '1087654321', '3009876543', 'maria.gomez@correo.com', 'Cll 45 # 12-08, Bucaramanga'),
 ('Luis', 'Torres', '1076543210', '3012345678', 'luis.torres@correo.com', 'Cra 27 # 33-10, Floridablanca');

-- Usuarios de prueba (contraseñas ya hasheadas con password_hash / bcrypt)
-- Admin123 / Recep123 / Vet123 / Cliente123
INSERT INTO usuarios (nombre, apellido, email, password_hash, rol_id, propietario_id) VALUES
 ('Ana', 'Administradora', 'admin@controlvet.com', '$2y$10$9oXTK3pgbOdymzt5HNeBueQFDTmBbHUD6HgS/wVeoubW7ZbxHctUG', 1, NULL),
 ('Paula', 'Recepción', 'recepcion@controlvet.com', '$2y$10$gZBaq3qAe3KI2oCO.5mSAOclw7X9mQlXZgb7aq5LENnthwgcXwVjG', 2, NULL),
 ('Dr. Jorge', 'Martínez', 'veterinario@controlvet.com', '$2y$10$RNLTnWpgnxLg6Ynh7IrDlOOLNg7CPlFfRvOim9hcPDqVxrzF8Av36', 3, NULL),
 ('Carlos', 'Ramírez', 'cliente@controlvet.com', '$2y$10$GC1XoMf69joG.NBVSxsSRuI73NhYyJdX0UCjzTUDwZKMCkY/Fu6ye', 4, 1);

-- Mascotas de prueba
INSERT INTO mascotas (nombre, especie, raza, sexo, fecha_nacimiento, peso_actual, color, propietario_id, observaciones) VALUES
 ('Rocky', 'Perro', 'Labrador', 'macho', '2021-03-10', 28.50, 'Dorado', 1, 'Alergia leve a pollo'),
 ('Michi', 'Gato', 'Criollo', 'hembra', '2022-06-01', 4.20, 'Negro', 1, NULL),
 ('Luna', 'Perro', 'Poodle', 'hembra', '2020-11-20', 8.10, 'Blanco', 2, NULL),
 ('Max', 'Perro', 'Bóxer', 'macho', '2019-05-15', 25.00, 'Atigrado', 3, 'Displasia de cadera controlada');

-- Citas de prueba
INSERT INTO citas (mascota_id, veterinario_id, fecha, hora, motivo, estado, creado_por) VALUES
 (1, 3, CURDATE(), '09:00:00', 'Control anual y vacunación', 'confirmada', 2),
 (3, 3, CURDATE(), '11:30:00', 'Revisión por cojera', 'programada', 2),
 (2, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '14:00:00', 'Esterilización - valoración previa', 'programada', 2),
 (4, 3, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '10:00:00', 'Chequeo displasia', 'atendida', 2);

-- Historias clínicas de prueba (histórico inmutable)
INSERT INTO historias_clinicas (mascota_id, veterinario_id, cita_id, motivo_consulta, peso, sintomas, diagnostico, tratamiento, observaciones) VALUES
 (4, 3, 4, 'Chequeo displasia', 25.00, 'Leve cojera en tren posterior', 'Displasia de cadera grado I, estable', 'Condroprotector y control de peso', 'Reevaluar en 6 meses'),
 (1, 3, NULL, 'Vacunación anual previa', 27.80, 'Ninguno', 'Paciente sano', 'Refuerzo vacuna polivalente', 'Próxima dosis en 12 meses');

-- Fórmula médica de prueba
INSERT INTO formulas_medicas (historia_id, medicamento, dosis, frecuencia, duracion, indicaciones) VALUES
 (1, 'Condroitina + Glucosamina', '1 tableta', 'Cada 24 horas', '30 días', 'Administrar con alimento. Controlar peso semanalmente.');

-- Productos de inventario de prueba
INSERT INTO productos (nombre, categoria, tipo, descripcion, cantidad_disponible, stock_minimo, precio_referencia) VALUES
 ('Vacuna polivalente canina', 'Biológico', 'Vacuna', 'Vacuna 5 en 1 para perros', 40, 10, 35000),
 ('Antiparasitario oral gato', 'Medicamento', 'Antiparasitario', 'Tableta antiparasitaria felina', 8, 10, 18000),
 ('Alimento premium adulto 15kg', 'Alimento', 'Concentrado', 'Alimento balanceado para perro adulto', 15, 5, 120000),
 ('Jeringas 5ml', 'Insumo', 'Material médico', 'Caja x100 unidades', 3, 5, 25000),
 ('Suero fisiológico 500ml', 'Insumo', 'Solución', 'Solución salina estéril', 20, 8, 9000);
