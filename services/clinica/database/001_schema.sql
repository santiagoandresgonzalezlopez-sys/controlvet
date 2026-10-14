-- =====================================================================
-- Servicio clínica · esquema propio cv_clinica
-- mascota_id / veterinario_id / cita_id son referencias lógicas a otros
-- servicios. propietario_id es copia del dueño al registrar la consulta
-- (aislamiento de datos del rol cliente). La FK formulas -> historias se
-- conserva porque ambas tablas pertenecen a este servicio.
-- =====================================================================

CREATE TABLE IF NOT EXISTS historias_clinicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mascota_id INT NOT NULL,
    propietario_id INT NOT NULL,
    veterinario_id INT NOT NULL,
    cita_id INT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motivo_consulta VARCHAR(200) NOT NULL,
    peso DECIMAL(6,2) NULL,
    sintomas TEXT NULL,
    diagnostico TEXT NOT NULL,
    tratamiento TEXT NULL,
    observaciones TEXT NULL,
    INDEX idx_historias_mascota (mascota_id),
    INDEX idx_historias_propietario (propietario_id),
    INDEX idx_historias_veterinario (veterinario_id),
    INDEX idx_historias_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS formulas_medicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    historia_id INT NOT NULL,
    medicamento VARCHAR(150) NOT NULL,
    dosis VARCHAR(80) NOT NULL,
    frecuencia VARCHAR(80) NOT NULL,
    duracion VARCHAR(80) NOT NULL,
    indicaciones TEXT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_formulas_historia FOREIGN KEY (historia_id) REFERENCES historias_clinicas(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
