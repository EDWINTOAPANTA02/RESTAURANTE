-- ============================================================
-- MIGRACIÓN COMPLETA: botanero_ventas → SaaS Multitenant + SRI
-- Ejecutar en orden. Usar en base de datos existente.
-- ============================================================

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
    -- SRI
    ambiente         TINYINT NOT NULL DEFAULT 1 COMMENT '1=Pruebas 2=Produccion',
    tipo_emision     TINYINT NOT NULL DEFAULT 1 COMMENT '1=Normal online',
    -- SaaS
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
ALTER TABLE usuarios
    ADD COLUMN empresa_id BIGINT UNSIGNED NOT NULL DEFAULT 1
        COMMENT 'FK multitenant' AFTER id,
    ADD CONSTRAINT fk_usuarios_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    ADD INDEX idx_empresa (empresa_id),
    MODIFY COLUMN correo VARCHAR(100) NOT NULL;

-- Hacer el correo único por empresa (no globalmente)
ALTER TABLE usuarios DROP INDEX correo;
ALTER TABLE usuarios ADD UNIQUE KEY uk_correo_empresa (correo, empresa_id);

ALTER TABLE categorias
    ADD COLUMN empresa_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id,
    ADD CONSTRAINT fk_categorias_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    ADD INDEX idx_empresa (empresa_id);

ALTER TABLE insumos
    ADD COLUMN empresa_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id,
    ADD CONSTRAINT fk_insumos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    ADD INDEX idx_empresa (empresa_id);

-- Hacer codigo único por empresa (no globalmente)
ALTER TABLE insumos DROP INDEX codigo;
ALTER TABLE insumos ADD UNIQUE KEY uk_codigo_empresa (codigo, empresa_id);

ALTER TABLE informacion_negocio
    ADD COLUMN empresa_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id,
    ADD CONSTRAINT fk_info_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id);

ALTER TABLE ventas
    ADD COLUMN empresa_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id,
    ADD CONSTRAINT fk_ventas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    ADD INDEX idx_empresa (empresa_id);

ALTER TABLE insumos_venta
    ADD COLUMN empresa_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id,
    ADD CONSTRAINT fk_insumos_venta_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    ADD INDEX idx_empresa (empresa_id);

ALTER TABLE pedidos
    ADD COLUMN empresa_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id,
    ADD CONSTRAINT fk_pedidos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    ADD INDEX idx_empresa (empresa_id);

ALTER TABLE detalles_pedidos
    ADD COLUMN empresa_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id,
    ADD CONSTRAINT fk_detalles_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    ADD INDEX idx_empresa (empresa_id);

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

-- Secuenciales: acceso exclusivo con SELECT ... FOR UPDATE
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

--
-- VENTAS = registro interno del pedido (sin cambios en su semántica original)
-- FACTURAS = comprobante tributario SRI (puede generarse o no después de una venta)
--

CREATE TABLE IF NOT EXISTS facturas (
    id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    empresa_id       BIGINT UNSIGNED NOT NULL,
    sucursal_id      BIGINT UNSIGNED NOT NULL,
    punto_emision_id BIGINT UNSIGNED NOT NULL,
    venta_id         BIGINT UNSIGNED NULL COMMENT 'NULL si factura manual sin venta previa',
    cliente_id       BIGINT UNSIGNED NULL COMMENT 'NULL = Consumidor Final',
    -- Numeración SRI: SSS-PPP-NNNNNNNNN
    numero_serie     VARCHAR(17) NOT NULL,
    clave_acceso     VARCHAR(49) NOT NULL UNIQUE COMMENT '49 digitos SRI',
    -- Ambiente en que fue EMITIDA (independiente del estado actual de la empresa)
    ambiente_sri     TINYINT NOT NULL COMMENT '1=Pruebas 2=Produccion',
    -- Fechas
    fecha_emision    DATETIME NOT NULL,
    -- Valores (protegidos: se calculan SOLO en backend, nunca en frontend)
    subtotal_0       DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal_iva     DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Base imponible con IVA',
    descuento        DECIMAL(12,2) NOT NULL DEFAULT 0,
    iva_valor        DECIMAL(12,2) NOT NULL DEFAULT 0,
    total            DECIMAL(12,2) NOT NULL,
    -- Estado SRI
    estado_sri       ENUM('BORRADOR','PENDIENTE','EN_PROCESO','AUTORIZADA','RECHAZADA','ANULADA')
                     NOT NULL DEFAULT 'BORRADOR',
    fecha_autorizacion DATETIME NULL,
    numero_autorizacion VARCHAR(49) NULL,
    -- Documentos XML (filesystem recomendado; aquí guardamos la ruta)
    xml_path         VARCHAR(500) NULL COMMENT 'Ruta al XML firmado en disco',
    xml_autorizado   LONGTEXT NULL COMMENT 'XML de respuesta SRI',
    respuesta_sri    JSON NULL COMMENT 'Respuesta completa del WS',
    mensaje_sri      TEXT NULL COMMENT 'Mensaje de error/rechazo para mostrar al usuario',
    -- Trazabilidad
    usuario_id       BIGINT UNSIGNED NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Control de concurrencia: una serie única por empresa
    UNIQUE KEY uk_empresa_serie (empresa_id, numero_serie),
    UNIQUE KEY uk_clave_acceso (clave_acceso),
    FOREIGN KEY (empresa_id)       REFERENCES empresas(id),
    FOREIGN KEY (sucursal_id)      REFERENCES sucursales(id),
    FOREIGN KEY (punto_emision_id) REFERENCES puntos_emision(id),
    FOREIGN KEY (venta_id)         REFERENCES ventas(id) ON DELETE SET NULL,
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
    -- Código SRI: 01=Efectivo 04=Tarjeta 05=Transferencia 17=Dinero electronico
    codigo_sri   VARCHAR(4) NOT NULL DEFAULT '01',
    descripcion  VARCHAR(100) NOT NULL,
    valor        DECIMAL(12,2) NOT NULL,
    plazo        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Dias plazo 0=contado',
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);

-- Vincular ventas con su posible factura
ALTER TABLE ventas
    ADD COLUMN factura_id BIGINT UNSIGNED NULL DEFAULT NULL,
    ADD COLUMN cliente_id BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Consumidor final si NULL',
    ADD CONSTRAINT fk_ventas_factura FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_ventas_cliente_r FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL;

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
