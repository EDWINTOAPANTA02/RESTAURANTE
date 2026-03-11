<?php
$host = "localhost";
$usuario = "root";
$password = "";
$port = 3307;
$dbname = "botanero_ventas";

try {
    $conexion = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $usuario, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tablas = ["usuarios", "insumos", "ventas", "mesas", "gastos"];
    foreach ($tablas as $tabla) {
        // Check if table exists
        $tableExists = $conexion->query("SHOW TABLES LIKE \"$tabla\"")->rowCount() > 0;
        if (!$tableExists) continue;

        $checkCol = $conexion->query("SHOW COLUMNS FROM $tabla LIKE \"empresa_id\"");
        if ($checkCol->rowCount() == 0) {
            $conexion->exec("ALTER TABLE $tabla ADD COLUMN empresa_id BIGINT UNSIGNED NOT NULL DEFAULT 1 FIRST");
            echo "Columna empresa_id añadida a $tabla.\n";
        } else {
            echo "Columna empresa_id ya existe en $tabla.\n";
        }
    }

} catch (PDOException $e) {
    echo "Error de BD: " . $e->getMessage() . "\n";
}

