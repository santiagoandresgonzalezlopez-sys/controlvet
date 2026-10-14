-- =====================================================================
-- Servicio pacientes · esquema propio cv_pacientes
-- Propiedad exclusiva: propietarios y mascotas. La FK mascotas ->
-- propietarios se conserva porque ambas tablas pertenecen al mismo servicio.
-- =====================================================================

CREATE TABLE IF NOT EXISTS propietarios (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mascotas (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
