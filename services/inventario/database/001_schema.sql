-- =====================================================================
-- Servicio inventario · esquema propio cv_inventario
-- =====================================================================

CREATE TABLE IF NOT EXISTS productos (
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
    CONSTRAINT chk_productos_cantidad CHECK (cantidad_disponible >= 0),
    CONSTRAINT chk_productos_minimo CHECK (stock_minimo >= 0),
    INDEX idx_productos_nombre (nombre),
    INDEX idx_productos_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
