<?php
$host = "localhost";
$usuario = "root";
$password = "";
$port = 3307;
$dbname = "botanero_ventas";

try {
    $conexion = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $usuario, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create pagos_empresa table
    $sqlPagos = "CREATE TABLE IF NOT EXISTS pagos_empresa (
        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        empresa_id BIGINT UNSIGNED NOT NULL,
        monto DECIMAL(10,2) NOT NULL,
        metodo_pago VARCHAR(50) NOT NULL,
        fecha_pago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        mes_pagado VARCHAR(20) NOT NULL,
        fecha_vencimiento_nueva DATE NOT NULL,
        notas TEXT,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    )";
    $conexion->exec($sqlPagos);
    echo "Tabla pagos_empresa creada o ya existe.\n";

    // 2. Ensure fecha_vencimiento exists in empresas
    // Check if column exists first
    $checkCol = $conexion->query("SHOW COLUMNS FROM empresas LIKE \"fecha_vencimiento\"");
    if ($checkCol->rowCount() == 0) {
        $conexion->exec("ALTER TABLE empresas ADD COLUMN fecha_vencimiento DATE DEFAULT NULL AFTER fecha_registro");
        echo "Columna fecha_vencimiento añadida a empresas.\n";
    } else {
        echo "Columna fecha_vencimiento ya existe.\n";
    }

    // 3. Set a default expiration for existing companies (e.g., 30 days from now)
    $conexion->exec("UPDATE empresas SET fecha_vencimiento = DATE_ADD(CURDATE(), INTERVAL 30 DAY) WHERE fecha_vencimiento IS NULL");
    echo "Fechas de vencimiento inicializadas para empresas existentes.\n";

} catch (PDOException $e) {
    echo "Error de BD: " . $e->getMessage() . "\n";
}

