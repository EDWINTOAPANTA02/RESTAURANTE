CREATE DATABASE IF NOT EXISTS botanero_ventas;
USE botanero_ventas;

CREATE TABLE categorias (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tipo ENUM('PLATILLO', 'BEBIDA') NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE insumos (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(100) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    precio DECIMAL(10,2) NOT NULL,
    tipo ENUM('PLATILLO', 'BEBIDA') NOT NULL,
    categoria BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_insumos_categoria FOREIGN KEY (categoria) REFERENCES categorias(id) ON DELETE CASCADE
);

CREATE TABLE informacion_negocio (
    id INT PRIMARY KEY DEFAULT 1,
    nombre VARCHAR(100),
    telefono VARCHAR(15),
    numeroMesas TINYINT, 
    logo VARCHAR(255),
    CONSTRAINT chk_single_row CHECK (id = 1)
);

CREATE TABLE usuarios (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    correo VARCHAR(100) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    rol ENUM('ADMINISTRADOR', 'MESERO', 'CAJERO') NOT NULL DEFAULT 'MESERO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ventas (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    idMesa TINYINT NOT NULL,
    cliente VARCHAR(100),
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL,
    pagado DECIMAL(10,2) NOT NULL,
    idUsuario BIGINT UNSIGNED NOT NULL,
    INDEX idx_fecha (fecha),
    CONSTRAINT fk_ventas_usuario FOREIGN KEY (idUsuario) REFERENCES usuarios(id)
);

CREATE TABLE insumos_venta (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    idInsumo BIGINT UNSIGNED NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    cantidad INT NOT NULL,
    idVenta BIGINT UNSIGNED NOT NULL,
    CONSTRAINT fk_insumos_venta_idInsumo FOREIGN KEY (idInsumo) REFERENCES insumos(id),
    CONSTRAINT fk_insumos_venta_idVenta FOREIGN KEY (idVenta) REFERENCES ventas(id) ON DELETE CASCADE
);

-- Nueva tabla para gestionar pedidos activos
CREATE TABLE pedidos (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    idMesa TINYINT NOT NULL,
    idUsuario BIGINT UNSIGNED NOT NULL,
    cliente VARCHAR(100),
    fecha_apertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('ABIERTO', 'PAGADO', 'CANCELADO') DEFAULT 'ABIERTO',
    INDEX idx_mesa_abierta (idMesa, estado),
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (idUsuario) REFERENCES usuarios(id)
);

CREATE TABLE detalles_pedidos (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    idPedido BIGINT UNSIGNED NOT NULL,
    idInsumo BIGINT UNSIGNED NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_detalles_idPedido FOREIGN KEY (idPedido) REFERENCES pedidos(id) ON DELETE CASCADE,
    CONSTRAINT fk_detalles_idInsumo FOREIGN KEY (idInsumo) REFERENCES insumos(id)
);