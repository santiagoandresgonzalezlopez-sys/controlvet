-- =====================================================================
-- Servicio citas · esquema propio cv_citas
-- mascota_id / veterinario_id / creado_por son referencias lógicas a los
-- servicios pacientes y auth (sin FK entre esquemas). propietario_id es una
-- copia del dueño de la mascota al agendar, usada para aislar datos del
-- rol cliente sin consultar otro servicio en cada lectura.
-- =====================================================================

CREATE TABLE IF NOT EXISTS citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mascota_id INT NOT NULL,
    propietario_id INT NOT NULL,
    veterinario_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    motivo VARCHAR(200) NOT NULL,
    estado ENUM('programada','confirmada','atendida','cancelada') NOT NULL DEFAULT 'programada',
    observaciones TEXT NULL,
    creado_por INT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_citas_fecha (fecha),
    INDEX idx_citas_estado (estado),
    INDEX idx_citas_veterinario (veterinario_id, fecha, hora),
    INDEX idx_citas_propietario (propietario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
