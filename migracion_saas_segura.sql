-- ============================================================
-- MIGRACIÓN SEGURA E IDEMPOTENTE: botanero_ventas → SaaS Multitenant + SRI
-- Ejecutable múltiples veces sin errores. Protege datos existentes.
-- ============================================================

-- Preparar procedimientos almacenados para alterar tablas de forma segura
DELIMITER //

CREATE PROCEDURE add_column_if_not_exists(
    IN dbName VARCHAR(255),
    IN tableName VARCHAR(255),
    IN columnName VARCHAR(255),
    IN columnDefinition TEXT
)
BEGIN
    DECLARE schema_count INT;
    SELECT COUNT(*) INTO schema_count 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = dbName AND TABLE_NAME = tableName AND COLUMN_NAME = columnName;
    
    IF schema_count = 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', tableName, ' ADD COLUMN ', columnName, ' ', columnDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

CREATE PROCEDURE drop_index_if_exists(
    IN dbName VARCHAR(255),
    IN tableName VARCHAR(255),
    IN indexName VARCHAR(255)
)
BEGIN
    DECLARE schema_count INT;
    SELECT COUNT(*) INTO schema_count 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = dbName AND TABLE_NAME = tableName AND INDEX_NAME = indexName;
    
    IF schema_count > 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', tableName, ' DROP INDEX ', indexName);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

CREATE PROCEDURE add_index_if_not_exists(
    IN dbName VARCHAR(255),
    IN tableName VARCHAR(255),
    IN indexName VARCHAR(255),
    IN indexDefinition TEXT
)
BEGIN
    DECLARE schema_count INT;
    SELECT COUNT(*) INTO schema_count 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = dbName AND TABLE_NAME = tableName AND INDEX_NAME = indexName;
    
    IF schema_count = 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', tableName, ' ADD ', indexDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

CREATE PROCEDURE add_fk_if_not_exists(
    IN dbName VARCHAR(255),
    IN tableName VARCHAR(255),
    IN constraintName VARCHAR(255),
    IN fkDefinition TEXT
)
BEGIN
    DECLARE schema_count INT;
    SELECT COUNT(*) INTO schema_count 
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = dbName AND TABLE_NAME = tableName AND CONSTRAINT_NAME = constraintName AND CONSTRAINT_TYPE = 'FOREIGN KEY';
    
    IF schema_count = 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', tableName, ' ADD CONSTRAINT ', constraintName, ' ', fkDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

DELIMITER ;

SET @current_db = DATABASE();

-- ── FASE 1: MULTITENANT BASE ─────────────────────────────────

-- 1.1 Empresas (tenants)
CREATE TABLE IF NOT EXISTS empresas (
    id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    razon_social     VARCHAR(200) NOT NULL,
    nombre_comercial VARCHAR(200),
    ruc              VARCHAR(13)  NOT NULL UNIQUE,
    direccion_matriz VARCHAR(300) NOT NULL,
    telefono         VARCHAR(20),
    correo           VARCHAR(150),
    ambiente         TINYINT NOT NULL DEFAULT 1 COMMENT '1=Pruebas 2=Produccion',
    tipo_emision     TINYINT NOT NULL DEFAULT 1 COMMENT '1=Normal online',
    plan             ENUM('BASICO','PRO','ENTERPRISE') NOT NULL DEFAULT 'BASICO',
    estado           ENUM('ACTIVO','SUSPENDIDO','CANCELADO') NOT NULL DEFAULT 'ACTIVO',
    fecha_registro   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento DATE NULL,
    INDEX idx_ruc (ruc),
    INDEX idx_estado (estado)
);

-- 1.2 Insertar empresa por defecto para datos actuales
INSERT IGNORE INTO empresas (id, razon_social, nombre_comercial, ruc, direccion_matriz, plan, estado)
VALUES (1, 'Empresa Principal', 'Botanero', '9999999999001', 'Sin dirección', 'PRO', 'ACTIVO');

-- 1.3 Agregar empresa_id a TODAS las tablas existentes
CALL add_column_if_not_exists(@current_db, 'usuarios', 'empresa_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 1 COMMENT "FK multitenant" AFTER id');
CALL add_fk_if_not_exists(@current_db, 'usuarios', 'fk_usuarios_empresa', 'FOREIGN KEY (empresa_id) REFERENCES empresas(id)');
CALL add_index_if_not_exists(@current_db, 'usuarios', 'idx_empresa', 'INDEX idx_empresa (empresa_id)');

CALL drop_index_if_exists(@current_db, 'usuarios', 'correo');
CALL add_index_if_not_exists(@current_db, 'usuarios', 'uk_correo_empresa', 'UNIQUE KEY uk_correo_empresa (correo, empresa_id)');

CALL add_column_if_not_exists(@current_db, 'categorias', 'empresa_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id');
CALL add_fk_if_not_exists(@current_db, 'categorias', 'fk_categorias_empresa', 'FOREIGN KEY (empresa_id) REFERENCES empresas(id)');
CALL add_index_if_not_exists(@current_db, 'categorias', 'idx_empresa_cat', 'INDEX idx_empresa_cat (empresa_id)');

CALL add_column_if_not_exists(@current_db, 'insumos', 'empresa_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id');
CALL add_fk_if_not_exists(@current_db, 'insumos', 'fk_insumos_empresa', 'FOREIGN KEY (empresa_id) REFERENCES empresas(id)');
CALL add_index_if_not_exists(@current_db, 'insumos', 'idx_empresa_ins', 'INDEX idx_empresa_ins (empresa_id)');
CALL drop_index_if_exists(@current_db, 'insumos', 'codigo');
CALL add_index_if_not_exists(@current_db, 'insumos', 'uk_codigo_empresa', 'UNIQUE KEY uk_codigo_empresa (codigo, empresa_id)');

CALL add_column_if_not_exists(@current_db, 'informacion_negocio', 'empresa_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id');
CALL add_fk_if_not_exists(@current_db, 'informacion_negocio', 'fk_info_empresa', 'FOREIGN KEY (empresa_id) REFERENCES empresas(id)');

CALL add_column_if_not_exists(@current_db, 'ventas', 'empresa_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id');
CALL add_fk_if_not_exists(@current_db, 'ventas', 'fk_ventas_empresa', 'FOREIGN KEY (empresa_id) REFERENCES empresas(id)');
CALL add_index_if_not_exists(@current_db, 'ventas', 'idx_empresa_ven', 'INDEX idx_empresa_ven (empresa_id)');

CALL add_column_if_not_exists(@current_db, 'insumos_venta', 'empresa_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id');
CALL add_fk_if_not_exists(@current_db, 'insumos_venta', 'fk_insumos_venta_empresa', 'FOREIGN KEY (empresa_id) REFERENCES empresas(id)');
CALL add_index_if_not_exists(@current_db, 'insumos_venta', 'idx_empresa_insv', 'INDEX idx_empresa_insv (empresa_id)');

CALL add_column_if_not_exists(@current_db, 'pedidos', 'empresa_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id');
CALL add_fk_if_not_exists(@current_db, 'pedidos', 'fk_pedidos_empresa', 'FOREIGN KEY (empresa_id) REFERENCES empresas(id)');
CALL add_index_if_not_exists(@current_db, 'pedidos', 'idx_empresa_ped', 'INDEX idx_empresa_ped (empresa_id)');

CALL add_column_if_not_exists(@current_db, 'detalles_pedidos', 'empresa_id', 'BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id');
CALL add_fk_if_not_exists(@current_db, 'detalles_pedidos', 'fk_detalles_empresa', 'FOREIGN KEY (empresa_id) REFERENCES empresas(id)');
CALL add_index_if_not_exists(@current_db, 'detalles_pedidos', 'idx_empresa_detp', 'INDEX idx_empresa_detp (empresa_id)');

-- ── FASE 1: CLIENTES ─────────────────────────────────────────

CREATE TABLE IF NOT EXISTS clientes (
    id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    empresa_id     BIGINT UNSIGNED NOT NULL,
    nombres        VARCHAR(100) NOT NULL,
    apellidos      VARCHAR(100) NOT NULL,
    tipo_id        ENUM('CEDULA','RUC','PASAPORTE') NOT NULL DEFAULT 'CEDULA',
    cedula_ruc     VARCHAR(20)  NOT NULL,
    telefono       VARCHAR(20),
    direccion      VARCHAR(255),
    correo         VARCHAR(100),
    estado         ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_cedula_empresa (cedula_ruc, empresa_id),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    INDEX idx_empresa (empresa_id),
    INDEX idx_cedula (cedula_ruc),
    INDEX idx_estado (estado)
);

-- ── FASE 2: SUCURSALES + PUNTOS DE EMISIÓN ──────────────────

CREATE TABLE IF NOT EXISTS sucursales (
    id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    empresa_id  BIGINT UNSIGNED NOT NULL,
    codigo      VARCHAR(3)   NOT NULL COMMENT 'SRI: 001',
    nombre      VARCHAR(150) NOT NULL,
    direccion   VARCHAR(300) NOT NULL,
    telefono    VARCHAR(20),
    estado      ENUM('ACTIVA','INACTIVA') DEFAULT 'ACTIVA',
    UNIQUE KEY uk_emp_sucursal (empresa_id, codigo),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    INDEX idx_empresa (empresa_id)
);

CREATE TABLE IF NOT EXISTS puntos_emision (
    id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    empresa_id   BIGINT UNSIGNED NOT NULL,
    sucursal_id  BIGINT UNSIGNED NOT NULL,
    codigo       VARCHAR(3) NOT NULL COMMENT 'SRI: 001',
    nombre       VARCHAR(100),
    estado       ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
    UNIQUE KEY uk_sucursal_caja (sucursal_id, codigo),
    FOREIGN KEY (empresa_id)  REFERENCES empresas(id),
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id),
    INDEX idx_empresa (empresa_id)
);

CREATE TABLE IF NOT EXISTS secuenciales (
    id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    empresa_id       BIGINT UNSIGNED NOT NULL,
    punto_emision_id BIGINT UNSIGNED NOT NULL,
    tipo_comprobante ENUM('FACTURA','NOTA_CREDITO','NOTA_DEBITO','RETENCION') NOT NULL,
    ultimo_numero    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uk_secuencial (empresa_id, punto_emision_id, tipo_comprobante),
    FOREIGN KEY (empresa_id)       REFERENCES empresas(id),
    FOREIGN KEY (punto_emision_id) REFERENCES puntos_emision(id)
);

-- ── FASE 3: CERTIFICADOS DIGITALES ──────────────────────────

CREATE TABLE IF NOT EXISTS certificados_digitales (
    id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    empresa_id     BIGINT UNSIGNED NOT NULL UNIQUE,
    ruta_archivo   VARCHAR(500) NOT NULL COMMENT 'Ruta absoluta fuera del webroot',
    passphrase_enc TEXT NOT NULL COMMENT 'Cifrada con AES-256',
    fecha_expira   DATE NOT NULL,
    activo         BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);

-- ── FASE 4: FACTURAS (separadas de ventas) ───────────────────

CREATE TABLE IF NOT EXISTS facturas (
    id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    empresa_id       BIGINT UNSIGNED NOT NULL,
    sucursal_id      BIGINT UNSIGNED NOT NULL,
    punto_emision_id BIGINT UNSIGNED NOT NULL,
    venta_id         BIGINT UNSIGNED NULL COMMENT 'NULL si factura manual sin venta previa',
    cliente_id       BIGINT UNSIGNED NULL COMMENT 'NULL = Consumidor Final',
    numero_serie     VARCHAR(17) NOT NULL,
    clave_acceso     VARCHAR(49) NOT NULL UNIQUE COMMENT '49 digitos SRI',
    ambiente_sri     TINYINT NOT NULL COMMENT '1=Pruebas 2=Produccion',
    fecha_emision    DATETIME NOT NULL,
    subtotal_0       DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal_iva     DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Base imponible con IVA',
    descuento        DECIMAL(12,2) NOT NULL DEFAULT 0,
    iva_valor        DECIMAL(12,2) NOT NULL DEFAULT 0,
    total            DECIMAL(12,2) NOT NULL,
    estado_sri       ENUM('BORRADOR','PENDIENTE','EN_PROCESO','AUTORIZADA','RECHAZADA','ANULADA') NOT NULL DEFAULT 'BORRADOR',
    fecha_autorizacion DATETIME NULL,
    numero_autorizacion VARCHAR(49) NULL,
    xml_path         VARCHAR(500) NULL COMMENT 'Ruta al XML firmado en disco',
    xml_autorizado   LONGTEXT NULL COMMENT 'XML de respuesta SRI',
    respuesta_sri    JSON NULL COMMENT 'Respuesta completa del WS',
    mensaje_sri      TEXT NULL COMMENT 'Mensaje de error/rechazo para mostrar al usuario',
    usuario_id       BIGINT UNSIGNED NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_empresa_serie (empresa_id, numero_serie),
    UNIQUE KEY uk_clave_acceso (clave_acceso),
    FOREIGN KEY (empresa_id)       REFERENCES empresas(id),
    FOREIGN KEY (sucursal_id)      REFERENCES sucursales(id),
    FOREIGN KEY (punto_emision_id) REFERENCES puntos_emision(id),
    FOREIGN KEY (cliente_id)       REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id)       REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_empresa_estado (empresa_id, estado_sri),
    INDEX idx_fecha (fecha_emision)
);

CREATE TABLE IF NOT EXISTS detalles_factura (
    id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    factura_id       BIGINT UNSIGNED NOT NULL,
    empresa_id       BIGINT UNSIGNED NOT NULL,
    descripcion      VARCHAR(300) NOT NULL,
    codigo_principal VARCHAR(50),
    cantidad         DECIMAL(12,4) NOT NULL DEFAULT 1,
    precio_unitario  DECIMAL(12,4) NOT NULL,
    descuento        DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal         DECIMAL(12,2) NOT NULL,
    tarifa_iva       TINYINT NOT NULL DEFAULT 15 COMMENT '0 5 12 15',
    iva_valor        DECIMAL(12,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);

CREATE TABLE IF NOT EXISTS pagos_factura (
    id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    factura_id   BIGINT UNSIGNED NOT NULL,
    empresa_id   BIGINT UNSIGNED NOT NULL,
    codigo_sri   VARCHAR(4) NOT NULL DEFAULT '01',
    descripcion  VARCHAR(100) NOT NULL,
    valor        DECIMAL(12,2) NOT NULL,
    plazo        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Dias plazo 0=contado',
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);

-- Vincular ventas con su posible factura usando procedimientos seguros:
CALL add_column_if_not_exists(@current_db, 'ventas', 'factura_id', 'BIGINT UNSIGNED NULL DEFAULT NULL');
CALL add_column_if_not_exists(@current_db, 'ventas', 'cliente_id', 'BIGINT UNSIGNED NULL DEFAULT NULL COMMENT "Consumidor final si NULL"');
CALL add_column_if_not_exists(@current_db, 'ventas', 'tipo_factura', 'ENUM("FACTURA","CONSUMIDOR_FINAL") NOT NULL DEFAULT "CONSUMIDOR_FINAL"');

-- Como facturas_venta se conecta ahora a facturas, usamos las FK seguras:
-- (Asegurarse que facturas exista antes)
CALL add_fk_if_not_exists(@current_db, 'facturas', 'fk_facturas_venta', 'FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE SET NULL');
CALL add_fk_if_not_exists(@current_db, 'ventas', 'fk_ventas_factura', 'FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE SET NULL');
CALL add_fk_if_not_exists(@current_db, 'ventas', 'fk_ventas_cliente_r', 'FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL');

-- ── AUDITORÍA ────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS audit_logs (
    id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    empresa_id    BIGINT UNSIGNED NOT NULL,
    usuario_id    BIGINT UNSIGNED NULL,
    accion        VARCHAR(100) NOT NULL,
    tabla         VARCHAR(50),
    registro_id   BIGINT UNSIGNED,
    ip            VARCHAR(45),
    datos_antes   JSON,
    datos_despues JSON,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa_id),
    INDEX idx_accion_tabla (accion, tabla),
    INDEX idx_fecha (created_at)
);

-- ── SUSCRIPCIONES ────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS suscripciones (
    id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    empresa_id   BIGINT UNSIGNED NOT NULL,
    plan         ENUM('BASICO','PRO','ENTERPRISE') NOT NULL,
    precio_mes   DECIMAL(8,2) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin    DATE,
    estado       ENUM('ACTIVO','VENCIDO','CANCELADO') DEFAULT 'ACTIVO',
    FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    INDEX idx_empresa (empresa_id)
);

-- ── AJUSTES DE USUARIOS (SUPERADMIN Y CÉDULA) ────────────────
CALL add_column_if_not_exists('botanero_ventas', 'usuarios', 'cedula', 'VARCHAR(20) NULL AFTER nombre');
ALTER TABLE usuarios MODIFY COLUMN rol ENUM('SUPERADMIN','ADMINISTRADOR','MESERO','CAJERO') NOT NULL DEFAULT 'MESERO';

-- Limpiar los procedimientos para no ensuciar la base final
DROP PROCEDURE IF EXISTS add_column_if_not_exists;
DROP PROCEDURE IF EXISTS drop_index_if_exists;
DROP PROCEDURE IF EXISTS add_index_if_not_exists;
DROP PROCEDURE IF EXISTS add_fk_if_not_exists;
