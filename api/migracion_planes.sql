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
