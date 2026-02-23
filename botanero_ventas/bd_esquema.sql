CREATE DATABASE botanero_ventas;
USE botanero_ventas;

CREATE TABLE categorias(
	id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
	tipo ENUM("PLATILLO", "BEBIDA") NOT NULL,
	nombre VARCHAR(50) NOT NULL,
	descripcion VARCHAR(255)
);

CREATE TABLE insumos(
	id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
	codigo VARCHAR(100) NOT NULL,
	nombre VARCHAR(100) NOT NULL,
	descripcion VARCHAR(255) NOT NULL,
	precio DECIMAL(6,2) NOT NULL,
	tipo ENUM("PLATILLO", "BEBIDA") NOT NULL,
	categoria BIGINT UNSIGNED NOT NULL,
    CONSTRAINT fk_insumos_categoria FOREIGN KEY (categoria) REFERENCES categorias(id) ON DELETE CASCADE
);

CREATE TABLE informacion_negocio(
	nombre VARCHAR(100),
	telefono VARCHAR(15),
	numeroMesas TINYINT, 
	logo VARCHAR(255)
);

CREATE TABLE usuarios(
	id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
	correo VARCHAR(100) NOT NULL UNIQUE,
	nombre VARCHAR(100) NOT NULL,
	telefono VARCHAR(20) NOT NULL,
	password VARCHAR(255) NOT NULL,
    rol ENUM('ADMINISTRADOR', 'MESERO', 'CAJERO') NOT NULL DEFAULT 'MESERO'
);

CREATE TABLE ventas(
	id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
	idMesa TINYINT NOT NULL,
	cliente VARCHAR(100),
	fecha DATETIME NOT NULL,
	total DECIMAL(6,2) NOT NULL,
	pagado DECIMAL(6,2) NOT NULL,
	idUsuario BIGINT UNSIGNED NOT NULL,
    CONSTRAINT fk_ventas_usuario FOREIGN KEY (idUsuario) REFERENCES usuarios(id)
);

CREATE TABLE insumos_venta(
	id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
	idInsumo BIGINT UNSIGNED NOT NULL,
	precio DECIMAL(6,2) NOT NULL,
	cantidad INT NOT NULL,
	idVenta BIGINT UNSIGNED NOT NULL,
    CONSTRAINT fk_insumos_venta_idInsumo FOREIGN KEY (idInsumo) REFERENCES insumos(id),
    CONSTRAINT fk_insumos_venta_idVenta FOREIGN KEY (idVenta) REFERENCES ventas(id) ON DELETE CASCADE
);

-- Nueva tabla para gestionar pedidos activos (reemplaza CSVs)
CREATE TABLE pedidos(
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    idMesa TINYINT NOT NULL UNIQUE,
    idUsuario BIGINT UNSIGNED NOT NULL,
    cliente VARCHAR(100),
    fecha_apertura DATETIME NOT NULL,
    estado ENUM('ABIERTO', 'PAGADO', 'CANCELADO') DEFAULT 'ABIERTO',
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (idUsuario) REFERENCES usuarios(id)
);

CREATE TABLE detalles_pedidos(
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    idPedido BIGINT UNSIGNED NOT NULL,
    idInsumo BIGINT UNSIGNED NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(6,2) NOT NULL,
    CONSTRAINT fk_detalles_idPedido FOREIGN KEY (idPedido) REFERENCES pedidos(id) ON DELETE CASCADE,
    CONSTRAINT fk_detalles_idInsumo FOREIGN KEY (idInsumo) REFERENCES insumos(id)
);