CREATE TABLE IF NOT EXISTS configuracion_planes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_plan VARCHAR(50) NOT NULL UNIQUE,
    precio DECIMAL(10, 2) NOT NULL,
    duracion_meses INT DEFAULT 1,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO configuracion_planes (nombre_plan, precio, duracion_meses) VALUES
('BASICO', 15.00, 1),
('PRO', 30.00, 1),
('ENTERPRISE', 60.00, 1),
('PREMIUM', 60.00, 1)
ON DUPLICATE KEY UPDATE precio = VALUES(precio), duracion_meses = VALUES(duracion_meses);

ALTER TABLE detalles_pedidos ADD COLUMN IF NOT EXISTS caracteristicas VARCHAR(255) DEFAULT '';
ALTER TABLE detalles_pedidos ADD COLUMN IF NOT EXISTS estado VARCHAR(20) DEFAULT 'pendiente';
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS metodo_pago VARCHAR(30) DEFAULT 'EFECTIVO';
ALTER TABLE insumos ADD COLUMN IF NOT EXISTS stock INT NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS cierres_caja (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    fecha_apertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre DATETIME NULL,
    monto_inicial DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_efectivo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_transferencia DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_tarjeta DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    gastos DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    efectivo_contado DECIMAL(10,2) NULL,
    diferencia DECIMAL(10,2) NULL,
    observaciones VARCHAR(255) DEFAULT '',
    estado ENUM('ABIERTA', 'CERRADA') DEFAULT 'ABIERTA',
    KEY idx_empresa_estado (empresa_id, estado)
);


