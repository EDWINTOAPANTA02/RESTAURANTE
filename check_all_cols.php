<?php
$host = 'localhost';
$usuario = 'root';
$password = '';
$port = 3307;
$dbname = 'botanero_ventas';

try {
    $conexion = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $usuario, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = ['informacion_negocio', 'empresas', 'categorias'];
    foreach ($tables as $t) {
        $rs = $conexion->query("SHOW COLUMNS FROM $t");
        $cols = $rs->fetchAll(PDO::FETCH_COLUMN);
        echo "Columns in $t: " . implode(", ", $cols) . "\n";
    }

}
catch (PDOException $e) {
    echo "Error de BD: " . $e->getMessage() . "\n";
}
