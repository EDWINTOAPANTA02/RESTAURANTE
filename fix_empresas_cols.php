<?php
$host = 'localhost';
$usuario = 'root';
$password = '';
$port = 3307;
$dbname = 'botanero_ventas';

try {
    $conexion = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $usuario, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $rs = $conexion->query("SHOW COLUMNS FROM empresas");
    $columns = $rs->fetchAll(PDO::FETCH_COLUMN);
    echo "COLUMNS IN EMPRESAS: " . implode(", ", $columns) . "\n";

    if (!in_array('ruc', $columns)) {
        echo "RUC MISSING! Adding it...\n";
        $conexion->exec("ALTER TABLE empresas ADD COLUMN ruc VARCHAR(20) AFTER razon_social");
    }

    if (!in_array('direccion_matriz', $columns)) {
        echo "direccion_matriz MISSING! Adding it...\n";
        $conexion->exec("ALTER TABLE empresas ADD COLUMN direccion_matriz VARCHAR(255) AFTER ruc");
    }

    echo "SCHEMA OK.\n";

}
catch (PDOException $e) {
    echo "Error de BD: " . $e->getMessage() . "\n";
}
