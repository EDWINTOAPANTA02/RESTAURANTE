<?php
include_once "encabezado.php";
include_once "funciones.php";

try {
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT COUNT(*) AS resultado FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()");
    $resultado = $sentencia->fetchAll();
    echo json_encode($resultado[0]);
} catch (\Throwable $e) {
    echo json_encode((object)["resultado" => 0]);
}
