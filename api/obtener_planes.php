<?php
include_once "encabezado.php";
include_once "funciones.php";
include_once "funciones_planes.php";

echo json_encode(obtenerPlanes());
?>
