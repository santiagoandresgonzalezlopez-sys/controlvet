-- =====================================================================
-- Servicio documentos · esquema propio cv_documentos
-- referencia_id apunta a una cita, historia o fórmula (otros servicios).
-- propietario_id se guarda al generar el documento para que el listado
-- del rol cliente no dependa de consultas entre esquemas (en v1.0.0 se
-- resolvía con subconsultas sobre citas/historias/fórmulas/mascotas).
-- =====================================================================

CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('comprobante_cita','resumen_consulta','formula_medica') NOT NULL,
    referencia_id INT NOT NULL,
    nombre_archivo VARCHAR(180) NOT NULL,
    generado_por INT NULL,
    propietario_id INT NOT NULL,
    fecha_generacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_documentos_tipo (tipo),
    INDEX idx_documentos_propietario (propietario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
