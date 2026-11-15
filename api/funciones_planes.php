<?php
include_once __DIR__ . "/funciones.php";

function obtenerPlanes() {
    $bd = conectarBaseDatos();
    $sentencia = $bd->query("SELECT * FROM configuracion_planes ORDER BY precio ASC");
    return $sentencia->fetchAll();
}

function actualizarPrecioPlan($id, $precio, $duracion) {
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("UPDATE configuracion_planes SET precio = ?, duracion_meses = ? WHERE id = ?");
    return $sentencia->execute([$precio, $duracion, $id]);
}

function obtenerPrecioPlan($nombre_plan) {
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT precio FROM configuracion_planes WHERE nombre_plan = ?");
    $sentencia->execute([$nombre_plan]);
    $res = $sentencia->fetchObject();
    return $res ? $res->precio : 15.00;
}
?>
